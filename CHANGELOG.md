# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased][unreleased]
### Added

### Changed

### Fixed

### Removed

## [2.1.0]
### Changed
 - Updated `nikic/php-parser` to `^3.0` (#154)
 - Dropped `php-school/psx` in favour of `kadet/keylighter` (#154)
 - Updated `aydin-hassan/cli-md-renderer` to `^2.2` which also uses `kadet/keylighter` instead of `php-school/psx` (#154)

## [2.0.0]
### Added
 - Added new exercise runner (Custom Runner) which allows for an exercise to not require a php solution. For example an exercise can now request the student install a piece of software and then the exercise will verify that it was installed. (#141)
 - Global function for specifying an event listener as lazy. Eg the listener is registered in the container and should be pulled at runtime (#138)
 - Exercise runners now return their required checks via getRequiredChecks (#137)
 - Each runner now requires a factory which implements `ExerciseRunnerFactoryInterface` which can add arguments to the command and create instances of the runner (#137)

### Changed
 - Refactor results and result renderers and improve the verification output (#142)
 - CLI exercises can now return an array of argument arrays which will run the program with each set of arguments, just like CGI exercises (BC is preserved here - 1 set of arguments is still accepted) (#142)
 - Event listener config format has changed. Listeners must be grouped under an arbitrary key (think name of the feature requiring the listeners - see PR for example) (#138)
 - Refactored some listeners to use more specific events and event objects (#140)
 - Extract getSolution to it's own interface `ProvidesSolution`. BC is preserved as CliExercise & CgiExercise now extend from it (#139)
 - Refactor everything dealing with the input file to use an `Input` object where the command line arguments can be retrieved from. This is BC break for checks, commands, self checking exercises and event listeners dealing with the `fileName` parameter (#135)

## [1.2.0]
 
### Added
 - Added ability to register event listeners via config as either callables or the name of a callable container entry (#133)
 - Added an event dispatch for whenever an exercise is selected via the menu (#134)

## [1.1.0]
### Fixed
 - Menu items now update the status when progress is reset (#131, #86)
 - Added tests for ResultRendererFactory and fixed the interface validation (#126)
   
### Changed
 - Improved message when a solution fails (#129)  
 
### Added
 - Added feature to put workshop in tutorial mode where exercises must be completed one after another (#127)
 - Updated dependencies (5d16877)
 - Added list support to markdown problem files (#132)
