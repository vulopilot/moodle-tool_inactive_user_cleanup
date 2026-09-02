<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The Inactive user cleanup
 *
 * @package    tool_inactive_user_cleanup
 * @copyright  DualCube (https://dualcube.com)
 * @author     DualCube <admin@dualcube.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_inactive_user_cleanup\task;

/**
 * Scheduled task for Inactive user cleanup.
 *
 * @copyright DualCube (https://dualcube.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_inactive_user_cleanup_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'tool_inactive_user_cleanup');
    }

    /**
     * Execute.
     */
    public function execute() {
        global $DB;
        mtrace(get_string('taskstart', 'tool_inactive_user_cleanup'));

        $inactivity = get_config('tool_inactive_user_cleanup', 'daysofinactivity');
        if ($inactivity == 0) {
            mtrace(get_string('invalaliddayofinactivity', 'tool_inactive_user_cleanup'));
            return;
        }

        $beforedelete = get_config('tool_inactive_user_cleanup', 'daysbeforedeletion');
        $subject = get_config('tool_inactive_user_cleanup', 'emailsubject');
        $messagetext = html_to_text(get_config('tool_inactive_user_cleanup', 'emailbody'));
        $adminuser = get_admin();

        $users = $DB->get_records('user', ['deleted' => '0']);
        foreach ($users as $user) {
            if ($user->lastaccess == 0) {
                continue;
            }
            $this->notify_inactive_user($user, $inactivity, $subject, $messagetext, $adminuser);
            if ($beforedelete != 0) {
                $this->delete_notified_user($user, $beforedelete);
            }
        }

        mtrace(get_string('taskend', 'tool_inactive_user_cleanup'));
    }

    /**
     * Email an inactive user and record that they were notified, unless already notified.
     *
     * @param \stdClass $user
     * @param int $inactivity days of inactivity before a notification is due
     * @param string $subject
     * @param string $messagetext
     * @param \stdClass $adminuser
     */
    private function notify_inactive_user($user, $inactivity, $subject, $messagetext, $adminuser) {
        global $DB;
        $inactivedays = round((time() - $user->lastaccess) / 60 / 60 / 24);
        if ($inactivedays <= $inactivity) {
            return;
        }
        if ($DB->get_record('tool_inactive_user_cleanup', ['userid' => $user->id])) {
            return;
        }
        if (!email_to_user($user, $adminuser, $subject, $messagetext)) {
            return;
        }

        mtrace(get_string('userid', 'tool_inactive_user_cleanup'));
        mtrace($user->id . '---' . $user->email);
        mtrace(get_string('userinactivtime', 'tool_inactive_user_cleanup') . $inactivedays);
        mtrace('');

        $record = new \stdClass();
        $record->userid = $user->id;
        $record->emailsent = 1;
        $record->date = time();
        $DB->insert_record('tool_inactive_user_cleanup', $record, false);
    }

    /**
     * Delete a previously notified user once the notice period has elapsed.
     *
     * @param \stdClass $user
     * @param int $beforedelete days to wait after notification before deletion
     */
    private function delete_notified_user($user, $beforedelete) {
        global $DB;
        $notice = $DB->get_record('tool_inactive_user_cleanup', ['userid' => $user->id]);
        if (!$notice) {
            return;
        }
        $sincenotice = round((time() - $notice->date) / 60 / 60 / 24);
        if ($sincenotice <= $beforedelete || isguestuser($user->id)) {
            return;
        }

        delete_user($user);
        mtrace(get_string('deleteduser', 'tool_inactive_user_cleanup') . $user->id);
        mtrace(get_string('detetsuccess', 'tool_inactive_user_cleanup'));
    }
}
