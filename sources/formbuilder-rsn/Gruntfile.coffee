ALL_TASKS = ['jst:all', 'coffee:all', 'concat:forReason', 'concat:standalone', 'stylus:all', 'concat:combineCss', 'clean:compiled']

# formbuilder.js must be compiled in this order:
# 1. rivets-config
# 2. main
# 3. fields js
# 4. fields templates

module.exports = (grunt) ->

  path = require('path')
  exec = require('child_process').exec

  grunt.loadNpmTasks('grunt-contrib-coffee')
  grunt.loadNpmTasks('grunt-contrib-concat')
  grunt.loadNpmTasks('grunt-contrib-cssmin')
  grunt.loadNpmTasks('grunt-contrib-jst')
  grunt.loadNpmTasks('grunt-contrib-stylus')
  grunt.loadNpmTasks('grunt-contrib-uglify')
  grunt.loadNpmTasks('grunt-contrib-watch')
  grunt.loadNpmTasks('grunt-contrib-clean')
  grunt.loadNpmTasks('grunt-release')
  grunt.loadNpmTasks('grunt-karma')

  grunt.initConfig

    pkg: '<json:package.json>'
    srcFolder: 'src'
    compiledFolder: 'compiled'  # Temporary holding area.
    distFolder: 'dist'
    vendorFolder: 'vendor'
    testFolder: 'test'

    jst:
      all:
        options:
          namespace: 'Formbuilder.templates'
          processName: (filename) ->
            signalStr = "templates/" #strip extra filepath and extensions
            filename.slice(filename.indexOf(signalStr)+signalStr.length, filename.indexOf(".html"))

        files:
          '<%= compiledFolder %>/templates.js': '<%= srcFolder %>/templates/**/*.html'

    coffee:
      all:
        files:
          '<%= compiledFolder %>/scripts.js': [
            '<%= srcFolder %>/scripts/underscore_mixins.coffee'
            '<%= srcFolder %>/scripts/rivets-config.coffee'
            '<%= srcFolder %>/scripts/main.coffee'
            '<%= srcFolder %>/scripts/fields/**/*.coffee'
          ]

    concat:
      standalone:
        files:
          '<%= distFolder %>/formbuilder.js': '<%= compiledFolder %>/*.js'
          '<%= vendorFolder %>/js/vendor-standalone.js': [
            'bower_components/jquery/jquery.js'
            'bower_components/jquery-ui/ui/jquery.ui.core.js'
            'bower_components/jquery-ui/ui/jquery.ui.widget.js'
            'bower_components/jquery-ui/ui/jquery.ui.mouse.js'
            'bower_components/jquery-ui/ui/jquery.ui.draggable.js'
            'bower_components/jquery-ui/ui/jquery.ui.droppable.js'
            'bower_components/jquery-ui/ui/jquery.ui.sortable.js'
            'bower_components/jquery-ui/ui/jquery.ui.tooltip.js'
            'bower_components/jquery-ui/ui/jquery.ui.position.js'
            'bower_components/jquery-ui-touch-punch-improved/jquery.ui.touch-punch-improved.js'
            'bower_components/jquery.scrollWindowTo/index.js'
            'bower_components/underscore/underscore-min.js'
            'bower_components/underscore.mixin.deepExtend/index.js'
            'bower_components/rivets/dist/rivets.js'
            'bower_components/backbone/backbone.js'
            'bower_components/backbone-deep-model/src/deep-model.js'
          ]
      forReason:
        files:
          '<%= distFolder %>/formbuilder.js': '<%= compiledFolder %>/*.js'
          '<%= vendorFolder %>/js/vendor.js': [
            # removing jquery/jquery-ui as we already include those in Reason. For testing locally in index.html, use vendor-standalone.js
            'bower_components/jquery-ui-touch-punch-improved/jquery.ui.touch-punch-improved.js'
            'bower_components/jquery.scrollWindowTo/index.js'
            'bower_components/underscore/underscore-min.js'
            'bower_components/underscore.mixin.deepExtend/index.js'
            'bower_components/rivets/dist/rivets.js'
            'bower_components/backbone/backbone.js'
            'bower_components/backbone-deep-model/src/deep-model.js'
          ]

      # combines our compiled stylus file with the custom embedded font
      combineCss:
        files:
          '<%= distFolder %>/formbuilder.css': [
            '<%= compiledFolder %>/formbuilder-compiled.css'
            '<%= srcFolder %>/styles/form-elements-font.css'
          ]


    cssmin:
      dist:
        files:
          '<%= distFolder %>/formbuilder-min.css': '<%= distFolder %>/formbuilder.css'
          '<%= vendorFolder %>/css/vendor.css': 'bower_components/font-awesome/css/font-awesome.css'

    stylus:
      all:
        files:
          # changing this around; now we'll compile to a temp file, and then we'll run combineCss to bring form-elements-font into formbuilder.css
          '<%= compiledFolder %>/formbuilder-compiled.css': '<%= srcFolder %>/styles/**.styl'
          # '<%= distFolder %>/formbuilder.css': '<%= compiledFolder %>/**/*.css'
    
    clean:
      compiled:
        ['<%= compiledFolder %>']

    uglify:
      options: {
        mangle: true
      },
      dist:
        files:
          '<%= distFolder %>/formbuilder-min.js': '<%= distFolder %>/formbuilder.js'

    watch:
      all:
        files: ['<%= srcFolder %>/**/*.{coffee,styl,html}']
        tasks: ALL_TASKS

    # To test, run `grunt --no-write -v release`
    release:
      npm: false

    karma:
      unit:
        configFile: '<%= testFolder %>/karma.conf.coffee'


  grunt.registerTask 'default', ALL_TASKS
  grunt.registerTask 'dist', ['cssmin:dist', 'uglify:dist']
  grunt.registerTask 'test', ['dist', 'karma']
