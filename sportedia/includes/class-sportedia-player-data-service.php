<?php
if (!defined('ABSPATH')) exit;

class EESS_Student_Data_Service {

    /**
     * Valid Emirates in the United Arab Emirates
     */
    public static function get_valid_emirates() {
        return array(
            'أبوظبي' => 'أبوظبي',
            'دبي' => 'دبي',
            'الشارقة' => 'الشارقة',
            'عجمان' => 'عجمان',
            'أم القيوين' => 'أم القيوين',
            'رأس الخيمة' => 'رأس الخيمة',
            'الفجيرة' => 'الفجيرة',
            'Abu Dhabi' => 'أبوظبي',
            'Dubai' => 'دبي',
            'Sharjah' => 'الشارقة',
            'Ajman' => 'عجمان',
            'Umm Al Quwain' => 'أم القيوين',
            'Ras Al Khaimah' => 'رأس الخيمة',
            'Fujairah' => 'الفجيرة'
        );
    }

    /**
     * Normalize Emirate string input
     */
    public static function normalize_emirate($input) {
        $clean = trim($input);
        $valid = self::get_valid_emirates();
        foreach ($valid as $key => $official) {
            if (mb_strtolower($clean) === mb_strtolower($key)) {
                return $official;
            }
        }
        return 'أبوظبي';
    }

    /**
     * Normalize Grade input (numeric or string)
     */
    public static function normalize_grade($input) {
        $clean = trim(str_ireplace(array('المجموعة التدريبية', 'Grade', 'grade'), '', $input));
        $num = intval($clean);
        if ($num >= 1 && $num <= 12) {
            $grade_map = array(
                1 => 'المجموعة التدريبية الأول', 2 => 'المجموعة التدريبية الثاني', 3 => 'المجموعة التدريبية الثالث', 4 => 'المجموعة التدريبية الرابع',
                5 => 'المجموعة التدريبية الخامس', 6 => 'المجموعة التدريبية السادس', 7 => 'المجموعة التدريبية السابع', 8 => 'المجموعة التدريبية الثامن',
                9 => 'المجموعة التدريبية التاسع', 10 => 'المجموعة التدريبية العاشر', 11 => 'المجموعة التدريبية الحادي عشر', 12 => 'المجموعة التدريبية الثاني عشر'
            );
            return $grade_map[$num];
        }
        return !empty($input) ? sanitize_text_field($input) : 'المجموعة التدريبية الأول';
    }

    /**
     * Normalize Section input
     */
    public static function normalize_section($input) {
        if (class_exists('EESS_Org_Helper')) {
            $resolved = EESS_Org_Helper::normalize_section($input);
            return $resolved['ar'];
        }
        return 'أ';
    }

    /**
     * Normalize Special Needs boolean input
     */
    public static function normalize_special_needs($input) {
        $clean = mb_strtolower(trim($input));
        if (in_array($clean, array('نعم', 'yes', '1', 'true'))) {
            return 1;
        }
        return 0;
    }

    /**
     * Normalize multi-select Allergies input
     */
    public static function normalize_allergies($input) {
        if (empty($input)) return '';
        $items = array_map('trim', explode(';', $input));
        $cleaned = array();
        foreach ($items as $item) {
            if (!empty($item) && $item !== 'لا توجد حساسية' && $item !== 'No Known Allergy') {
                $cleaned[] = sanitize_text_field($item);
            }
        }
        return !empty($cleaned) ? implode('; ', array_unique($cleaned)) : 'لا توجد حساسية';
    }

    /**
     * Normalize financial numbers and calculate outstanding balance
     */
    public static function normalize_financials($total, $paid) {
        $total_val = max(0, floatval($total));
        $paid_val  = max(0, floatval($paid));
        $balance   = max(0, $total_val - $paid_val);
        $status    = ($balance <= 0 && $total_val > 0) ? 'Paid' : (($paid_val > 0) ? 'Partial' : 'Unpaid');

        return array(
            'total_tuition_fees'  => $total_val,
            'amount_paid'         => $paid_val,
            'outstanding_balance' => $balance,
            'fee_status'          => $status
        );
    }

    /**
     * Normalize and save a complete 30-field student record into the database
     */
    public static function process_and_save_student($data) {
        global $wpdb;
        SM_DB::ensure_student_columns_exist();

        $student_id = intval($data['id'] ?? ($data['student_id'] ?? 0));
        $raw_name   = trim(sanitize_text_field($data['name'] ?? ($data['full_name'] ?? '')));
        $raw_grade  = trim(sanitize_text_field($data['class_name'] ?? ($data['class'] ?? ($data['grade'] ?? ''))));
        $raw_sec    = trim(sanitize_text_field($data['section'] ?? ''));
        $raw_nat_id = trim(sanitize_text_field($data['national_id'] ?? ''));
        $raw_school = trim(sanitize_text_field($data['school_id'] ?? ($data['institution_id'] ?? ($data['school_code'] ?? ($data['institution_code'] ?? '')))));

        // Required fields validation: Name, School, Grade, and Section are mandatory
        if (empty($raw_name)) {
            return new WP_Error('missing_required_name', 'اسم اللاعب حقل إجباري.');
        }
        if (empty($raw_school)) {
            return new WP_Error('missing_required_school', 'رمز/اسم الأكاديمية الرياضية حقل إجباري.');
        }
        if (empty($raw_grade)) {
            return new WP_Error('missing_required_grade', 'المجموعة التدريبية الدراسي حقل إجباري.');
        }
        if (empty($raw_sec)) {
            return new WP_Error('missing_required_section', 'المجموعة التدريبية / المجموعة التدريبية حقل إجباري.');
        }

        $name        = $raw_name;
        $grade       = self::normalize_grade($raw_grade);
        $section     = self::normalize_section($raw_sec);
        $national_id = !empty($raw_nat_id) ? $raw_nat_id : null;

        // Server-Side Role and Institution Scope Validation
        $curr_user_id = get_current_user_id();
        if ($curr_user_id > 0) {
            $user_roles = (array) wp_get_current_user()->roles;
            $can_edit_student = current_user_can('manage_options') || current_user_can('إدارة_اللاعبين') || in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_discipline_supervisor', $user_roles);

            if (!$can_edit_student) {
                return new WP_Error('unauthorized', 'عفواً، لا تمتلك الصلاحية الكافية لإضافة أو تعديل بيانات اللاعبين.');
            }

            if (class_exists('EESS_Org_Helper')) {
                $user_scope = EESS_Org_Helper::get_user_scope($curr_user_id);
                if (!$user_scope['unrestricted']) {
                    $allowed_insts = array_map('intval', array_merge((array)($user_scope['institutions'] ?? array()), (array)($user_scope['schools'] ?? array())));
                    if ($student_id > 0) {
                        $existing_stu = $wpdb->get_row($wpdb->prepare("SELECT institution_id, school_id FROM {$wpdb->prefix}sm_students WHERE id = %d", $student_id));
                        if ($existing_stu) {
                            $st_inst = intval($existing_stu->institution_id ?: $existing_stu->school_id);
                            if ($st_inst > 0 && !empty($allowed_insts) && !in_array($st_inst, $allowed_insts, true)) {
                                return new WP_Error('access_denied', 'عفواً، لا تملك صلاحية تعديل لاعب ينتمي لمؤسسة أخرى.');
                            }
                        }
                    }
                }
            }
        }

        if (empty($name)) {
            return new WP_Error('missing_name', 'اسم اللاعب حقل إجباري.');
        }

        // De-duplication check during CSV Import / Creation (Resolve student_id before National ID check)
        $stu_code = sanitize_text_field($data['code'] ?? ($data['student_id_code'] ?? ($data['student_code'] ?? '')));
        if ($student_id == 0) {
            if (!empty($stu_code)) {
                $existing_by_code = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE student_code = %s", $stu_code));
                if ($existing_by_code) {
                    $student_id = intval($existing_by_code);
                }
            }
            if ($student_id == 0 && !empty($national_id)) {
                $existing_by_nat = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE national_id = %s", $national_id));
                if ($existing_by_nat) {
                    $student_id = intval($existing_by_nat);
                }
            }
            if ($student_id == 0 && !empty($name) && !empty($grade) && !empty($section)) {
                $existing_by_name = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_students WHERE name = %s AND class_name = %s AND section = %s", $name, $grade, $section));
                if ($existing_by_name) {
                    $student_id = intval($existing_by_name);
                }
            }
        }

        // Enforce Strict Uniqueness on National ID
        if (!empty($national_id)) {
            $existing_nat_stu = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sm_students WHERE national_id = %s AND id != %d",
                $national_id, $student_id
            ));
            if ($existing_nat_stu) {
                return new WP_Error('duplicate_national_id', 'رقم الهوية الوطنية مكرر — لم يتم قبول التسجيل.');
            }

            $existing_nat_user = username_exists($national_id);
            if ($existing_nat_user) {
                $linked_stu_id = get_user_meta($existing_nat_user, 'eess_student_id', true);
                if ($linked_stu_id && intval($linked_stu_id) !== $student_id) {
                    return new WP_Error('duplicate_national_id', 'رقم الهوية الوطنية مكرر — لم يتم قبول التسجيل.');
                }
            }
        }



        // Automatic Institution & School Scope Resolution from Organizational Structure
        $raw_input_org = $raw_school;

        // Enforce Institution Modification Restriction: Only System Administrators can change institution
        $user_roles = (array) wp_get_current_user()->roles;
        $is_sys_admin = in_array('administrator', $user_roles, true) || in_array('sm_system_admin', $user_roles, true) || current_user_can('manage_options');

        if ($student_id > 0 && !$is_sys_admin) {
            // Keep existing student institution unchanged if non-system admin
            $existing_inst = $wpdb->get_var($wpdb->prepare("SELECT institution_id FROM {$wpdb->prefix}sm_students WHERE id = %d", $student_id));
            if ($existing_inst) {
                $raw_input_org = intval($existing_inst);
            }
        }

        $institution_id = null;
        $school_id      = null;

        if (!empty($raw_input_org)) {
            if (is_numeric($raw_input_org)) {
                $inst_num = intval($raw_input_org);
                $inst_row = $wpdb->get_row($wpdb->prepare("SELECT id, code, name FROM {$wpdb->prefix}eess_institutions WHERE id = %d OR code = %d LIMIT 1", $inst_num, $inst_num));
            } else {
                $inst_row = $wpdb->get_row($wpdb->prepare("SELECT id, code, name FROM {$wpdb->prefix}eess_institutions WHERE name = %s OR code = %s LIMIT 1", $raw_input_org, $raw_input_org));
            }

            if ($inst_row) {
                $institution_id = intval($inst_row->id);
                $school_id      = intval($inst_row->id);
            }
        }

        // Default to primary institution from Organizational Structure if no match found
        if (empty($institution_id)) {
            $first_inst = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}eess_institutions ORDER BY id ASC LIMIT 1");
            if ($first_inst) {
                $institution_id = intval($first_inst);
                $school_id      = intval($first_inst);
            } else {
                $institution_id = 1;
                $school_id      = 1;
            }
        }

        // Resolve Department ID for Student Affairs (Department Code 3)
        $student_affairs_dept_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}eess_departments WHERE code = '3' OR name LIKE '%شؤون اللاعبين%' OR name LIKE '%شؤون اللاعبين%' ORDER BY id ASC LIMIT 1");
        if (!$student_affairs_dept_id) {
            $student_affairs_dept_id = 3;
        }

        $financials = self::normalize_financials($data['total_tuition_fees'] ?? 0, $data['amount_paid'] ?? 0);

        // Standardize Guardian Phone Number with Country Code
        $phone_country = sanitize_text_field($data['guardian_phone_country'] ?? '+971');
        $phone_number  = sanitize_text_field($data['guardian_phone'] ?? '');
        $full_phone    = !empty($phone_number) ? (strpos($phone_number, '+') === 0 ? $phone_number : trim($phone_country . ' ' . $phone_number)) : '';

        $fields = array(
            'name'                  => $name,
            'class_name'            => $grade,
            'section'               => $section,
            'gender'                => sanitize_text_field($data['gender'] ?? 'ذكر'),
            'dob'                   => !empty($data['dob']) ? sanitize_text_field($data['dob']) : null,
            'nationality'           => sanitize_text_field($data['nationality'] ?? 'الإمارات العربية المتحدة'),
            'national_id'           => $national_id,
            'institution_id'        => $institution_id ?: null,
            'school_id'             => $school_id ?: null,
            'department_id'         => intval($student_affairs_dept_id),
            'guardian_name'         => sanitize_text_field($data['guardian_name'] ?? ($data['parent_name'] ?? '')),
            'guardian_relationship' => sanitize_text_field($data['guardian_relationship'] ?? 'أب'),
            'parent_email'          => sanitize_email($data['parent_email'] ?? ($data['guardian_email'] ?? '')),
            'guardian_phone'        => $full_phone,
            'student_status'        => sanitize_text_field($data['student_status'] ?? 'Active'),
            'enrollment_status'     => sanitize_text_field($data['enrollment_status'] ?? 'Enrolled'),
            'enrollment_date'       => !empty($data['enrollment_date']) ? sanitize_text_field($data['enrollment_date']) : (!empty($data['registration_date']) ? sanitize_text_field($data['registration_date']) : date('Y-m-d')),
            'registration_date'     => !empty($data['registration_date']) ? sanitize_text_field($data['registration_date']) : date('Y-m-d'),
            'emirate'               => self::normalize_emirate($data['emirate'] ?? 'أبوظبي'),
            'address'               => sanitize_textarea_field($data['address'] ?? ''),
            'academic_level'        => sanitize_text_field($data['academic_level'] ?? 'ممتار'),
            'special_needs'         => self::normalize_special_needs($data['special_needs'] ?? 'لا'),
            'health_status'         => sanitize_textarea_field($data['health_status'] ?? 'سليم'),
            'allergies'             => self::normalize_allergies($data['allergies'] ?? ''),
            'photo_url'             => esc_url_raw($data['photo_url'] ?? ''),
            'fee_status'            => $financials['fee_status'],
            'total_tuition_fees'    => $financials['total_tuition_fees'],
            'amount_paid'           => $financials['amount_paid'],
            'outstanding_balance'   => $financials['outstanding_balance'],
            'payment_status'        => sanitize_text_field($data['payment_status'] ?? 'Pending')
        );

        if (!empty($data['student_code'])) {
            $fields['student_code'] = sanitize_text_field($data['student_code']);
        }
        if (!empty($data['parent_user_id'])) {
            $fields['parent_user_id'] = intval($data['parent_user_id']);
        }
        $fields['teacher_id'] = !empty($data['teacher_id']) ? intval($data['teacher_id']) : null;

        if ($student_id > 0) {
            $existing_stu_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_students WHERE id = %d", $student_id));
            if ($existing_stu_row) {
                // Preserve existing student data if newly provided field is empty or unsupplied during import merge
                foreach ($fields as $fk => $fv) {
                    if ((is_null($fv) || $fv === '' || $fv === '0000-00-00') && !empty($existing_stu_row->$fk) && $existing_stu_row->$fk !== '0000-00-00') {
                        $fields[$fk] = $existing_stu_row->$fk;
                    }
                }
                if (empty($fields['student_code']) && !empty($existing_stu_row->student_code)) {
                    $fields['student_code'] = $existing_stu_row->student_code;
                }
                if (empty($fields['national_id']) && !empty($existing_stu_row->national_id)) {
                    $fields['national_id'] = $existing_stu_row->national_id;
                }
            }

            // Check if institution was changed (transfer scenario)
            $existing_inst_id = $existing_stu_row ? intval($existing_stu_row->institution_id) : 0;
            if (empty($data['student_code']) && !empty($existing_inst_id) && intval($existing_inst_id) !== intval($institution_id)) {
                // Institution changed and no manual code provided: Regenerate code for new institution
                if (class_exists('EESS_ID_Code_Service')) {
                    $fields['student_code'] = EESS_ID_Code_Service::generate_student_code($institution_id);
                } else {
                    $fields['student_code'] = SM_DB::generate_student_code($institution_id);
                }
            }

            $updated = $wpdb->update("{$wpdb->prefix}sm_students", $fields, array('id' => $student_id));
            if ($updated === false) {
                return new WP_Error('db_update_failed', 'فشل تحديث بيانات اللاعب في قاعدة البيانات: ' . $wpdb->last_error);
            }
            $final_id = $student_id;

            // Database Persistence Verification: Query actual database record to verify institution ID was saved
            if ($institution_id > 0) {
                $saved_inst_id = $wpdb->get_var($wpdb->prepare("SELECT institution_id FROM {$wpdb->prefix}sm_students WHERE id = %d", $final_id));
                if (intval($saved_inst_id) !== intval($institution_id)) {
                    return new WP_Error('db_verification_failed', 'فشل التحقق من حفظ المنظمة الرياضية في قاعدة البيانات. لم تتم عملية الحفظ بنجاح.');
                }
            }
        } else {
            // Resolve institution ID for code generation
            $inst_id = $institution_id ?: 1;
            if (empty($fields['student_code'])) {
                if (class_exists('EESS_ID_Code_Service')) {
                    $generated_code = EESS_ID_Code_Service::generate_student_code($inst_id);
                } else {
                    $generated_code = SM_DB::generate_student_code($school_id);
                }
                $fields['student_code'] = $generated_code;
            }
            $inserted = $wpdb->insert("{$wpdb->prefix}sm_students", $fields);
            if ($inserted === false || !$wpdb->insert_id) {
                return new WP_Error('db_insert_failed', 'فشل إضافة اللاعب في قاعدة البيانات: ' . $wpdb->last_error);
            }
            $final_id = $wpdb->insert_id;

            // Database Persistence Verification for New Record
            if ($institution_id > 0) {
                $saved_inst_id = $wpdb->get_var($wpdb->prepare("SELECT institution_id FROM {$wpdb->prefix}sm_students WHERE id = %d", $final_id));
                if (intval($saved_inst_id) !== intval($institution_id)) {
                    return new WP_Error('db_verification_failed', 'فشل التحقق من حفظ المنظمة الرياضية في قاعدة البيانات. لم تتم عملية الحفظ بنجاح.');
                }
            }
        }

        // Invalidate object cache cleanly
        wp_cache_flush();

        if ($final_id > 0) {
            EESS_Org_Helper::resolve_student_org_ids($final_id, $grade, $section);

            // Behavior Record Integration: Create permanent behavior log if behavior observation note provided
            $behavior_note = sanitize_textarea_field($data['student_behavior'] ?? ($data['behavior_note'] ?? ''));
            if (!empty($behavior_note)) {
                $wpdb->insert("{$wpdb->prefix}sm_records", array(
                    'student_id'   => $final_id,
                    'type'         => 'ملاحظة سلوكية',
                    'degree'       => 1,
                    'severity'     => 'low',
                    'action_taken' => 'ملاحظة سلوكية مسجلة عند قيد/تعديل بيانات اللاعب',
                    'details'      => $behavior_note,
                    'status'       => 'approved',
                    'created_at'   => current_time('mysql')
                ));
            }

        }

        return $final_id;
    }
}
