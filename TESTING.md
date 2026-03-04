# Manual Testing Checklist for AS Courses v2.0

This document provides a comprehensive manual testing checklist for the AS Courses module after the v2.0 OOP refactoring.

## Pre-Testing Setup

```bash
# Clear all caches
drush cr

# Check module is enabled
drush pm:list | grep as_courses

# Import configuration (if needed)
drush config:import -y

# Check for errors
drush watchdog:show --severity=Error --count=20
```

## Phase 1: Core Services

### CoursesApiService

- [ ] **Service instantiation**
  ```bash
  drush php-eval "var_dump(\Drupal::hasService('as_courses.api'));"
  # Expected: bool(true)
  ```

- [ ] **API call by subject**
  ```bash
  drush php-eval "\$api = \Drupal::service('as_courses.api'); \$courses = \$api->getCoursesBySubject('SP26', 'PSYCH'); echo count(\$courses) . ' courses found';"
  # Expected: Number of courses found (e.g., "15 courses found")
  ```

- [ ] **API call by instructor**
  ```bash
  drush php-eval "\$api = \Drupal::service('as_courses.api'); \$courses = \$api->getCoursesByInstructor('SP26', 'VALID_NETID'); var_dump(\$courses);"
  # Replace VALID_NETID with actual NetID
  # Expected: Array of courses or NULL if none
  ```

- [ ] **Caching works**
  ```bash
  # First call (API hit)
  drush php-eval "timer_start('api'); \$api = \Drupal::service('as_courses.api'); \$api->getCoursesBySubject('SP26', 'PSYCH'); echo timer_read('api') . 'ms';"

  # Second call (cached, should be faster)
  drush php-eval "timer_start('cache'); \$api = \Drupal::service('as_courses.api'); \$api->getCoursesBySubject('SP26', 'PSYCH'); echo timer_read('cache') . 'ms';"
  # Expected: Second call significantly faster
  ```

- [ ] **Error handling**
  ```bash
  drush php-eval "\$api = \Drupal::service('as_courses.api'); \$courses = \$api->getCoursesBySubject('INVALID', 'INVALID'); var_dump(\$courses);"
  # Expected: NULL (error logged, no exception thrown)

  # Check error was logged
  drush watchdog:show --type=as_courses --count=5
  # Expected: Error message about API failure
  ```

### SemesterGeneratorService

- [ ] **Service instantiation**
  ```bash
  drush php-eval "var_dump(\Drupal::hasService('as_courses.semester_generator'));"
  # Expected: bool(true)
  ```

- [ ] **Generate semester options**
  ```bash
  drush php-eval "\$gen = \Drupal::service('as_courses.semester_generator'); \$opts = \$gen->getSemesterOptions(); print_r(array_slice(\$opts, 0, 10));"
  # Expected: Array with semester codes as keys and names as values
  # Example: ['FA25' => 'Fall 2025', 'WI26' => 'Winter 2026', ...]
  ```

- [ ] **Get current semester**
  ```bash
  drush php-eval "\$gen = \Drupal::service('as_courses.semester_generator'); echo \$gen->getCurrentSemester();"
  # Expected: Current semester code (e.g., 'SP26' if run in March 2026)
  ```

- [ ] **Format semester name**
  ```bash
  drush php-eval "\$gen = \Drupal::service('as_courses.semester_generator'); echo \$gen->formatSemesterName('SP26');"
  # Expected: 'Spring 2026'

  drush php-eval "\$gen = \Drupal::service('as_courses.semester_generator'); echo \$gen->formatSemesterName('FA25');"
  # Expected: 'Fall 2025'
  ```

- [ ] **Semesters between years**
  ```bash
  drush php-eval "\$gen = \Drupal::service('as_courses.semester_generator'); \$sems = \$gen->getSemestersBetween(2025, 2026); echo count(\$sems) . ' semesters';"
  # Expected: 8 semesters (4 per year: FA, WI, SP, SU)
  ```

## Phase 2: Twig Extensions

### parseCoursesJson

- [ ] **Twig function available**
  - Create a test content type or page
  - Add Twig template with: `{{ dump(parse_courses_json('SP26', 'PSYCH', 5, 'random')) }}`
  - Visit the page
  - **Expected**: Array of up to 5 courses displayed

- [ ] **Uses API service**
  - Check watchdog for API calls: `drush watchdog:show --type=as_courses`
  - **Expected**: No errors, API calls logged

### getCurrentSemesters

- [ ] **Twig function returns config**
  ```twig
  {{ dump(get_current_semesters()) }}
  ```
  - **Expected**: Array of semester codes selected in admin settings

### parseSemesterName

- [ ] **Formats semester correctly**
  ```twig
  {{ parse_semester_name('SP26') }}
  {{ parse_semester_name('FA25') }}
  {{ parse_semester_name('WI26') }}
  ```
  - **Expected**:
    - Spring 2026
    - Fall 2025
    - Winter 2026

### parsePersonCoursesNetidJson

- [ ] **Gets courses by NetID**
  ```twig
  {{ dump(parse_person_courses_netid_json('SP26', 'VALID_NETID')) }}
  ```
  - **Expected**: Array of courses for that instructor

### parsePersonCoursesJson

- [ ] **Filters by subject and NetID**
  ```twig
  {{ dump(parse_person_courses_json('SP26', 'PSYCH', 'VALID_NETID')) }}
  ```
  - **Expected**: Courses filtered to subject

### parsePersonCoursesNetidPrefixJson

- [ ] **Filters by subjects array**
  ```twig
  {% set subjects = ['PSYCH', 'ECON'] %}
  {{ dump(parse_person_courses_netid_prefix_json('SP26', subjects, 'VALID_NETID')) }}
  ```
  - **Expected**: Courses filtered to those subjects only

## Phase 3: Settings Form

### Form Rendering

- [ ] **Form loads**
  - Visit: `/admin/config/content/as_courses`
  - **Expected**: Form displays without errors

- [ ] **Dynamic semester checkboxes**
  - Check the semester checkboxes list
  - **Expected**:
    - Semesters from ~2025 to ~2031 (current year -1 to +5)
    - NOT hardcoded FA23-FA27
    - Options labeled like "Fall 2025", "Spring 2026"

- [ ] **Dynamic semester select**
  - Check the "Default semester" dropdown
  - **Expected**: Same dynamic list as checkboxes

- [ ] **Course prefixes field**
  - **Expected**: Text field for comma-separated prefixes

### Form Submission

- [ ] **Save configuration**
  - Select 2-3 semesters (e.g., SP26, FA26)
  - Choose a default semester (e.g., SP26)
  - Enter course prefixes: `PSYCH,ECON,HIST`
  - Click "Save configuration"
  - **Expected**: "The configuration options have been saved." message

- [ ] **Verify saved config**
  ```bash
  drush config:get as_courses.defaults
  ```
  - **Expected**: Shows selected values:
    - `semester:` array with selected codes
    - `defaultsemester: SP26`
    - `course_prefixes: 'PSYCH,ECON,HIST'`

- [ ] **No 'sort' value saved**
  ```bash
  drush config:get as_courses.defaults sort
  ```
  - **Expected**: Empty or null (bug fix)

### Bugs Fixed

- [ ] **parent::submitForm() called**
  - Save form
  - **Expected**: Standard Drupal success message appears (proves parent method called)

- [ ] **No MapArray import error**
  - Check for PHP errors after form save
  - **Expected**: No errors about unused imports

## Phase 4: CoursesController

### Route Access

- [ ] **Default route works**
  - Visit: `/courses`
  - **Expected**: Page loads (200 status)

- [ ] **Route with semester parameter**
  - Visit: `/courses/SP26`
  - **Expected**: Page loads with Spring 2026 data

- [ ] **Uses default semester**
  - Ensure default semester is set in admin (e.g., SP26)
  - Visit: `/courses` (no semester parameter)
  - **Expected**: Page shows SP26 courses (check page output or inspect render array)

### Dependency Injection

- [ ] **No static calls**
  ```bash
  grep -n "Drupal::config" /path/to/as_courses/src/Controller/CoursesController.php
  ```
  - **Expected**: No results (all replaced with `$this->configFactory`)

## Phase 5: Deprecated Functions

### Deprecation Notices

- [ ] **Old function still works**
  ```bash
  drush php-eval "\$courses = as_courses_get_courses_json('SP26', 'PSYCH'); echo count(\$courses) . ' courses';"
  ```
  - **Expected**: Courses returned, but deprecation notice logged

- [ ] **Deprecation logged**
  ```bash
  drush watchdog:show --type=php --count=5
  ```
  - **Expected**: Deprecation warning message:
    - "as_courses_get_courses_json() is deprecated in as_courses:2.0.0..."

- [ ] **NetID function deprecated**
  ```bash
  drush php-eval "\$courses = as_courses_get_courses_netid_json('SP26', 'VALID_NETID');"
  drush watchdog:show --type=php --count=5
  ```
  - **Expected**: Deprecation warning for `as_courses_get_courses_netid_json()`

## Phase 6: Overall Integration

### Configuration

- [ ] **Settings config exists**
  ```bash
  drush config:get as_courses.settings
  ```
  - **Expected**: Shows default values:
    - `semester_start_year_offset: -1`
    - `semester_end_year_offset: 5`
    - `api_timeout: 10`
    - `cache_duration: 3600`

### Performance

- [ ] **Cache working properly**
  ```bash
  # Check cache entry exists
  drush cache:get "as_courses:SP26:PSYCH"
  ```
  - **Expected**: Cache data present after API call

- [ ] **Page load performance**
  - Visit `/courses` multiple times
  - First load: Slower (API call)
  - Subsequent loads: Faster (cached)
  - **Expected**: Noticeable performance improvement

### Error Handling

- [ ] **Invalid API parameters**
  ```bash
  drush php-eval "\$api = \Drupal::service('as_courses.api'); \$api->getCoursesBySubject('BADCODE', 'BADSUB');"
  drush watchdog:show --type=as_courses --count=3
  ```
  - **Expected**: NULL returned, error logged (not exception thrown)

- [ ] **Network timeout handling**
  - Temporarily disconnect network (if possible)
  - Try loading `/courses`
  - **Expected**: Page loads gracefully (cached data or empty state), error logged

### Logging

- [ ] **Logger channel working**
  ```bash
  drush watchdog:show --type=as_courses
  ```
  - **Expected**: Shows API-related log entries

### Backward Compatibility

- [ ] **Existing templates work**
  - If templates use old Twig functions, verify they still work
  - **Expected**: No breaking changes

- [ ] **Old config preserved**
  ```bash
  drush config:get as_courses.defaults
  ```
  - **Expected**: Existing semester/prefix config maintained

## Automated Tests

### Unit Tests

```bash
# Run all unit tests
cd web/modules/custom/as_courses
vendor/bin/phpunit tests/src/Unit

# Expected: All tests pass
# CoursesApiServiceTest
# SemesterGeneratorServiceTest
```

### Functional Tests

```bash
# Run all functional tests
vendor/bin/phpunit tests/src/Functional

# Expected: All tests pass
# AsCoursesSettingsFormTest
# CoursesControllerTest
```

## Success Criteria Summary

✅ All core services instantiate without errors
✅ API calls work and cache properly
✅ Semester generator produces correct codes and labels
✅ All 6 Twig extensions work with dependency injection
✅ Settings form displays dynamic semesters (not hardcoded)
✅ Settings form saves without errors
✅ Bugs fixed (parent::submitForm, MapArray, 'sort' value)
✅ CoursesController uses dependency injection
✅ Old functions work but log deprecation notices
✅ All automated tests pass
✅ No PHP warnings, notices, or errors
✅ Site performs same or better than before refactoring
✅ Existing functionality preserved (backward compatible)

## Rollback Plan

If critical issues are found:

1. **Identify failing phase** from checklist
2. **Revert changes**:
   ```bash
   git revert <commit-hash>
   drush cr
   drush updb -y
   ```
3. **Verify site functional**
4. **Document issue** for future fix

## Sign-Off

- [ ] All checklist items completed
- [ ] All automated tests passing
- [ ] No critical errors in logs
- [ ] Performance acceptable
- [ ] Ready for production deployment

**Tested by**: _________________
**Date**: _________________
**Environment**: _________________
**Notes**: _________________
