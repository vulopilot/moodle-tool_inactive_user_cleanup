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
 * Standalone Grunt configuration for tool_inactive_user_cleanup.
 *
 * This plugin currently has no amd/src or scss sources - the tasks below
 * simply have nothing to do until some are added. It exists so this plugin
 * can be linted/built on its own, without checking out a full Moodle site.
 * CI (moodle-plugin-ci grunt) does not use this file: it runs Moodle core's
 * own Gruntfile against a full site instead.
 *
 * @copyright  DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        eslint: {
            amd: {
                src: ['amd/src/**/*.js'],
            },
        },

        uglify: {
            amd: {
                files: [{
                    expand: true,
                    cwd: 'amd/src',
                    src: ['**/*.js'],
                    dest: 'amd/build',
                    ext: '.min.js',
                }],
            },
        },

        watch: {
            amd: {
                files: ['amd/src/**/*.js'],
                tasks: ['eslint', 'uglify'],
            },
        },
    });

    grunt.loadNpmTasks('grunt-eslint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('amd', ['eslint', 'uglify']);
    grunt.registerTask('default', ['amd']);
};
