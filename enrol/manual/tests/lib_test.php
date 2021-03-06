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
 * Manual enrolment tests.
 *
 * @package    enrol_manual
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Manual enrolment tests.
 *
 * @package    enrol_manual
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_manual_lib_testcase extends advanced_testcase {
    /**
     * Test enrol migration function used when uninstalling enrol plugins.
     */
    public function test_migrate_plugin_enrolments() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/enrol/manual/locallib.php');

        $this->resetAfterTest();

        $manplugin = enrol_get_plugin('manual');

        // Setup a few courses and users.

        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->assertNotEmpty($teacherrole);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        $context1 = context_course::instance($course1->id);
        $context2 = context_course::instance($course2->id);
        $context3 = context_course::instance($course3->id);
        $context4 = context_course::instance($course4->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        // We expect manual, self and guest instances to be created by default.

        $this->assertEquals(5, $DB->count_records('enrol', array('enrol'=>'manual')));
        $this->assertEquals(5, $DB->count_records('enrol', array('enrol'=>'self')));
        $this->assertEquals(5, $DB->count_records('enrol', array('enrol'=>'guest')));
        $this->assertEquals(15, $DB->count_records('enrol', array()));

        $this->assertEquals(0, $DB->count_records('user_enrolments', array()));

        // Enrol some users to manual instances.

        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, array('id'=>$maninstance1->id));
        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', array('courseid'=>$course2->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $DB->delete_records('enrol', array('courseid'=>$course3->id, 'enrol'=>'manual'));
        $DB->delete_records('enrol', array('courseid'=>$course4->id, 'enrol'=>'manual'));
        $DB->delete_records('enrol', array('courseid'=>$course5->id, 'enrol'=>'manual'));

        $manplugin->enrol_user($maninstance1, $user1->id, $studentrole->id);
        $manplugin->enrol_user($maninstance1, $user2->id, $studentrole->id);
        $manplugin->enrol_user($maninstance1, $user3->id, $teacherrole->id);
        $manplugin->enrol_user($maninstance2, $user3->id, $teacherrole->id);

        $this->assertEquals(4, $DB->count_records('user_enrolments', array()));

        // Set up some bogus enrol plugin instances and enrolments.

        $xxxinstance1 = $DB->insert_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'xxx', 'status'=>ENROL_INSTANCE_ENABLED));
        $xxxinstance1 = $DB->get_record('enrol', array('id'=>$xxxinstance1));
        $xxxinstance3 = $DB->insert_record('enrol', array('courseid'=>$course3->id, 'enrol'=>'xxx', 'status'=>ENROL_INSTANCE_DISABLED));
        $xxxinstance3 = $DB->get_record('enrol', array('id'=>$xxxinstance3));
        $xxxinstance4 = $DB->insert_record('enrol', array('courseid'=>$course4->id, 'enrol'=>'xxx', 'status'=>ENROL_INSTANCE_ENABLED));
        $xxxinstance4 = $DB->get_record('enrol', array('id'=>$xxxinstance4));
        $xxxinstance4b = $DB->insert_record('enrol', array('courseid'=>$course4->id, 'enrol'=>'xxx', 'status'=>ENROL_INSTANCE_DISABLED));
        $xxxinstance4b = $DB->get_record('enrol', array('id'=>$xxxinstance4b));


        $DB->insert_record('user_enrolments', array('enrolid'=>$xxxinstance1->id, 'userid'=>$user1->id, 'status'=>ENROL_USER_SUSPENDED));
        role_assign($studentrole->id, $user1->id, $context1->id, 'enrol_xxx', $xxxinstance1->id);
        role_assign($teacherrole->id, $user1->id, $context1->id, 'enrol_xxx', $xxxinstance1->id);
        $DB->insert_record('user_enrolments', array('enrolid'=>$xxxinstance1->id, 'userid'=>$user4->id, 'status'=>ENROL_USER_ACTIVE));
        role_assign($studentrole->id, $user4->id, $context1->id, 'enrol_xxx', $xxxinstance1->id);
        $this->assertEquals(2, $DB->count_records('user_enrolments', array('enrolid'=>$xxxinstance1->id)));
        $this->assertEquals(6, $DB->count_records('role_assignments', array('contextid'=>$context1->id)));


        $DB->insert_record('user_enrolments', array('enrolid'=>$xxxinstance3->id, 'userid'=>$user1->id, 'status'=>ENROL_USER_ACTIVE));
        role_assign($studentrole->id, $user1->id, $context3->id, 'enrol_xxx', $xxxinstance3->id);
        $DB->insert_record('user_enrolments', array('enrolid'=>$xxxinstance3->id, 'userid'=>$user2->id, 'status'=>ENROL_USER_SUSPENDED));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array('enrolid'=>$xxxinstance3->id)));
        $this->assertEquals(1, $DB->count_records('role_assignments', array('contextid'=>$context3->id)));

        $DB->insert_record('user_enrolments', array('enrolid'=>$xxxinstance4->id, 'userid'=>$user1->id, 'status'=>ENROL_USER_ACTIVE));
        role_assign($studentrole->id, $user1->id, $context4->id, 'enrol_xxx', $xxxinstance4->id);
        $DB->insert_record('user_enrolments', array('enrolid'=>$xxxinstance4->id, 'userid'=>$user2->id, 'status'=>ENROL_USER_ACTIVE));
        role_assign($studentrole->id, $user2->id, $context4->id, 'enrol_xxx', $xxxinstance4->id);
        $DB->insert_record('user_enrolments', array('enrolid'=>$xxxinstance4b->id, 'userid'=>$user1->id, 'status'=>ENROL_USER_SUSPENDED));
        role_assign($teacherrole->id, $user1->id, $context4->id, 'enrol_xxx', $xxxinstance4b->id);
        $DB->insert_record('user_enrolments', array('enrolid'=>$xxxinstance4b->id, 'userid'=>$user4->id, 'status'=>ENROL_USER_ACTIVE));
        role_assign($teacherrole->id, $user4->id, $context4->id, 'enrol_xxx', $xxxinstance4b->id);
        $this->assertEquals(2, $DB->count_records('user_enrolments', array('enrolid'=>$xxxinstance4->id)));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array('enrolid'=>$xxxinstance4b->id)));
        $this->assertEquals(4, $DB->count_records('role_assignments', array('contextid'=>$context4->id)));

        // Finally do the migration.

        enrol_manual_migrate_plugin_enrolments('xxx');

        // Verify results.

        $this->assertEquals(1, $DB->count_records('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual')));
        $this->assertEquals(1, $DB->count_records('enrol', array('courseid'=>$course1->id, 'enrol'=>'xxx')));
        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $this->assertEquals(ENROL_INSTANCE_DISABLED, $maninstance1->status);
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid'=>$maninstance1->id, 'userid'=>$user1->id, 'status'=>ENROL_USER_ACTIVE)));
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid'=>$maninstance1->id, 'userid'=>$user2->id, 'status'=>ENROL_USER_ACTIVE)));
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid'=>$maninstance1->id, 'userid'=>$user3->id, 'status'=>ENROL_USER_ACTIVE)));
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid'=>$maninstance1->id, 'userid'=>$user4->id, 'status'=>ENROL_USER_ACTIVE)));
        $this->assertEquals(4, $DB->count_records('user_enrolments', array('enrolid'=>$maninstance1->id)));
        $this->assertEquals(0, $DB->count_records('user_enrolments', array('enrolid'=>$xxxinstance1->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('itemid'=>0, 'component'=>$DB->sql_empty(), 'userid'=>$user1->id, 'roleid'=>$studentrole->id, 'contextid'=>$context1->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('itemid'=>0, 'component'=>$DB->sql_empty(), 'userid'=>$user1->id, 'roleid'=>$teacherrole->id, 'contextid'=>$context1->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('itemid'=>0, 'component'=>$DB->sql_empty(), 'userid'=>$user2->id, 'roleid'=>$studentrole->id, 'contextid'=>$context1->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('itemid'=>0, 'component'=>$DB->sql_empty(), 'userid'=>$user3->id, 'roleid'=>$teacherrole->id, 'contextid'=>$context1->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('itemid'=>0, 'component'=>$DB->sql_empty(), 'userid'=>$user4->id, 'roleid'=>$studentrole->id, 'contextid'=>$context1->id)));
        $this->assertEquals(5, $DB->count_records('role_assignments', array('contextid'=>$context1->id)));


        $this->assertEquals(1, $DB->count_records('enrol', array('courseid'=>$course2->id, 'enrol'=>'manual')));
        $this->assertEquals(0, $DB->count_records('enrol', array('courseid'=>$course2->id, 'enrol'=>'xxx')));
        $maninstance2 = $DB->get_record('enrol', array('courseid'=>$course2->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $this->assertEquals(ENROL_INSTANCE_ENABLED, $maninstance2->status);


        $this->assertEquals(1, $DB->count_records('enrol', array('courseid'=>$course3->id, 'enrol'=>'manual')));
        $this->assertEquals(1, $DB->count_records('enrol', array('courseid'=>$course3->id, 'enrol'=>'xxx')));
        $maninstance3 = $DB->get_record('enrol', array('courseid'=>$course3->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $this->assertEquals(ENROL_INSTANCE_DISABLED, $maninstance3->status);
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid'=>$maninstance3->id, 'userid'=>$user1->id, 'status'=>ENROL_USER_ACTIVE)));
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid'=>$maninstance3->id, 'userid'=>$user2->id, 'status'=>ENROL_USER_SUSPENDED)));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array('enrolid'=>$maninstance3->id)));
        $this->assertEquals(0, $DB->count_records('user_enrolments', array('enrolid'=>$xxxinstance3->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('itemid'=>0, 'component'=>$DB->sql_empty(), 'userid'=>$user1->id, 'roleid'=>$studentrole->id, 'contextid'=>$context3->id)));
        $this->assertEquals(1, $DB->count_records('role_assignments', array('contextid'=>$context3->id)));


        $this->assertEquals(1, $DB->count_records('enrol', array('courseid'=>$course4->id, 'enrol'=>'manual')));
        $this->assertEquals(2, $DB->count_records('enrol', array('courseid'=>$course4->id, 'enrol'=>'xxx')));
        $maninstance4 = $DB->get_record('enrol', array('courseid'=>$course4->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $this->assertEquals(ENROL_INSTANCE_ENABLED, $maninstance4->status);
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid'=>$maninstance4->id, 'userid'=>$user1->id, 'status'=>ENROL_USER_ACTIVE)));
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid'=>$maninstance4->id, 'userid'=>$user2->id, 'status'=>ENROL_USER_ACTIVE)));
        $this->assertTrue($DB->record_exists('user_enrolments', array('enrolid'=>$maninstance4->id, 'userid'=>$user4->id, 'status'=>ENROL_USER_SUSPENDED)));
        $this->assertEquals(3, $DB->count_records('user_enrolments', array('enrolid'=>$maninstance4->id)));
        $this->assertEquals(0, $DB->count_records('user_enrolments', array('enrolid'=>$xxxinstance4->id)));
        $this->assertEquals(0, $DB->count_records('user_enrolments', array('enrolid'=>$xxxinstance4b->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('itemid'=>0, 'component'=>$DB->sql_empty(), 'userid'=>$user1->id, 'roleid'=>$studentrole->id, 'contextid'=>$context4->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('itemid'=>0, 'component'=>$DB->sql_empty(), 'userid'=>$user1->id, 'roleid'=>$teacherrole->id, 'contextid'=>$context4->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('itemid'=>0, 'component'=>$DB->sql_empty(), 'userid'=>$user2->id, 'roleid'=>$studentrole->id, 'contextid'=>$context4->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('itemid'=>0, 'component'=>$DB->sql_empty(), 'userid'=>$user4->id, 'roleid'=>$teacherrole->id, 'contextid'=>$context4->id)));
        $this->assertEquals(4, $DB->count_records('role_assignments', array('contextid'=>$context4->id)));


        $this->assertEquals(0, $DB->count_records('enrol', array('courseid'=>$course5->id, 'enrol'=>'manual')));
        $this->assertEquals(0, $DB->count_records('enrol', array('courseid'=>$course5->id, 'enrol'=>'xxx')));

        // Make sure wrong params do not produce errors or notices.

        enrol_manual_migrate_plugin_enrolments('manual');
        enrol_manual_migrate_plugin_enrolments('yyyy');
    }
}
