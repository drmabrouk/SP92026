<?php
if (!defined('ABSPATH')) exit;

class EESS_File_Naming_Service {

    /**
     * Generate filesystem-safe, standardized filename for Lesson Preparation documents.
     */
    public static function generate_lesson_prep_filename($prep) {
        if (!$prep) return 'Lesson_Prep.pdf';

        $prep_id = intval(is_object($prep) ? $prep->id : ($prep['id'] ?? 0));
        $teacher_id = intval(is_object($prep) ? $prep->teacher_id : ($prep['teacher_id'] ?? 0));
        $teacher_name = '';

        if ($teacher_id > 0) {
            $user = get_userdata($teacher_id);
            if ($user) $teacher_name = $user->display_name;
        }
        if (empty($teacher_name) && is_object($prep) && !empty($prep->teacher_name)) {
            $teacher_name = $prep->teacher_name;
        }
        if (empty($teacher_name)) $teacher_name = 'معلم_غير_محدد';

        $lesson_date = is_object($prep) ? ($prep->lesson_date ?? '') : ($prep['lesson_date'] ?? '');
        if (empty($lesson_date)) $lesson_date = date('Y-m-d');

        $subject = is_object($prep) ? ($prep->subject ?? '') : ($prep['subject'] ?? '');
        $ext = self::extract_extension(is_object($prep) ? ($prep->file_url ?? '') : ($prep['file_url'] ?? ''), 'pdf');

        $raw_name = sprintf('تحضير_درس_-_%s_-_%s_-_LP%d.%s', $teacher_name, $lesson_date, $prep_id, $ext);
        return self::sanitize_filename($raw_name);
    }

    /**
     * Generate filesystem-safe, standardized filename for Quarterly & Annual Plans.
     */
    public static function generate_term_plan_filename($plan) {
        if (!$plan) return 'Term_Plan.pdf';

        $plan_id = intval(is_object($plan) ? $plan->id : ($plan['id'] ?? 0));
        $teacher_id = intval(is_object($plan) ? $plan->teacher_id : ($plan['teacher_id'] ?? 0));
        $teacher_name = '';

        if ($teacher_id > 0) {
            $user = get_userdata($teacher_id);
            if ($user) $teacher_name = $user->display_name;
        }
        if (empty($teacher_name) && is_object($plan) && !empty($plan->teacher_name)) {
            $teacher_name = $plan->teacher_name;
        }
        if (empty($teacher_name)) $teacher_name = 'معلم_غير_محدد';

        $term_num = is_object($plan) ? ($plan->term_number ?? 1) : ($plan['term_number'] ?? 1);
        $subject = is_object($plan) ? ($plan->subject ?? '') : ($plan['subject'] ?? '');
        $ext = self::extract_extension(is_object($plan) ? ($plan->file_url ?? '') : ($plan['file_url'] ?? ''), 'pdf');

        $term_label = 'الفصل_' . $term_num;
        $raw_name = sprintf('خطة_فصلية_-_%s_-_%s_-_TP%d.%s', $teacher_name, $term_label, $plan_id, $ext);
        return self::sanitize_filename($raw_name);
    }

    /**
     * Extract extension from URL or path safely.
     */
    public static function extract_extension($file_url, $default = 'pdf') {
        if (empty($file_url)) return $default;
        $path_info = pathinfo(parse_url($file_url, PHP_URL_PATH));
        $ext = strtolower($path_info['extension'] ?? '');
        if (in_array($ext, array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'))) {
            return $ext;
        }
        return $default;
    }

    /**
     * Sanitize filename for safe zip archive insertion and HTTP headers.
     */
    public static function sanitize_filename($filename) {
        $filename = preg_replace('/[\r\n\t\0\x0B]/', '', $filename);
        $filename = str_replace(array('/', '\\', ':', '*', '?', '"', '<', '>', '|'), '_', $filename);
        $filename = preg_replace('/\s+/', ' ', $filename);
        return trim($filename);
    }
}
