# Changelog

All notable changes to the AS Courses module will be documented in this file.

## [2.0.0] - 2026-03-04

### Added

- **Core Services Architecture**
  - New `CoursesApiService` for Cornell Classes API interactions
  - New `SemesterGeneratorService` for dynamic semester list generation
  - Logger channel `as_courses` for better error tracking
  - Configuration schema and install files for module settings

- **Dynamic Semester Generation**
  - Semesters now generated programmatically based on configurable year ranges
  - No more manual updates needed when semesters change
  - Configuration: `semester_start_year_offset` (default: -1) and `semester_end_year_offset` (default: +5)

- **Dependency Injection Throughout**
  - All Twig extensions now use proper dependency injection
  - CoursesController uses dependency injection
  - AsCoursesSettingsForm uses dependency injection

- **Comprehensive Testing**
  - Unit tests for `CoursesApiService`
  - Unit tests for `SemesterGeneratorService`
  - Functional tests for `AsCoursesSettingsForm`
  - Functional tests for `CoursesController`

### Changed

- **Replaced cURL with GuzzleHttp**
  - API calls now use Drupal's `@http_client` service
  - Better error handling and logging
  - Configurable timeout settings

- **Settings Form Improvements**
  - Dynamic semester options replace hardcoded arrays
  - Removed hardcoded semesters (FA23-FA27)
  - Now automatically shows semesters from current year -1 to +5

- **Twig Extensions Refactored**
  - `parseCoursesJson` - Uses CoursesApiService
  - `parsePersonCoursesJson` - Uses CoursesApiService
  - `parsePersonCoursesNetidJson` - Uses CoursesApiService
  - `parsePersonCoursesNetidPrefixJson` - Uses CoursesApiService
  - `parseSemesterName` - Uses SemesterGeneratorService
  - `getCurrentSemesters` - Uses ConfigFactory via DI

- **Configuration Structure**
  - New `as_courses.settings` config for API and semester settings
  - Existing `as_courses.defaults` config maintained for backward compatibility

### Fixed

- **AsCoursesSettingsForm Bugs**
  - Added missing `parent::submitForm()` call
  - Removed unused `MapArray` import
  - Removed undefined `sort` value from config save operation

### Deprecated

- `as_courses_get_courses_json($semester, $subjects)`
  - Use `\Drupal::service('as_courses.api')->getCoursesBySubject($semester, $subjects)` instead
  - Will be removed in version 3.0.0

- `as_courses_get_courses_netid_json($semester, $netid)`
  - Use `\Drupal::service('as_courses.api')->getCoursesByInstructor($semester, $netid)` instead
  - Will be removed in version 3.0.0

### Security

- Proper error handling prevents API errors from exposing sensitive information
- All API calls logged for security auditing
- Input validation maintained throughout refactoring

## [1.x] - Prior versions

Previous versions used procedural functions and hardcoded semester lists. See git history for details.

---

## Upgrade Path

### From 1.x to 2.0.0

1. **Clear cache**: `drush cr`
2. **Run database updates**: `drush updb -y`
3. **Import configuration**: `drush config:import -y` (if config in sync folder)
4. **Visit settings form**: Go to `/admin/config/content/as_courses` and verify semester list is displayed
5. **Test functionality**: Verify course data loads on pages using the module
6. **Update custom code** (if applicable):
   - Replace calls to `as_courses_get_courses_json()` with service method
   - Replace calls to `as_courses_get_courses_netid_json()` with service method

### Backward Compatibility

Version 2.0.0 is **backward compatible** with existing templates and configurations:

- All Twig function signatures remain unchanged
- Deprecated functions still work (with deprecation notices)
- Existing configuration values preserved
- No template changes required

### Configuration Changes

New configuration file `as_courses.settings.yml` with:
- `semester_start_year_offset: -1`
- `semester_end_year_offset: 5`
- `api_timeout: 10`
- `cache_duration: 3600`

These can be adjusted at `/admin/config/development/configuration` or via `settings.php`.

### Breaking Changes (for 3.0.0)

The following will be removed in version 3.0.0:
- `as_courses_get_courses_json()` function
- `as_courses_get_courses_netid_json()` function

Update any custom code using these functions before upgrading to 3.0.0.

---

## Configuration Reference

### as_courses.settings

```yaml
semester_start_year_offset: -1  # Years before current year to start generating semesters
semester_end_year_offset: 5     # Years after current year to end generating semesters
api_timeout: 10                 # API request timeout in seconds
cache_duration: 3600            # Cache duration in seconds (default: 1 hour)
```

### as_courses.defaults

```yaml
semester:                       # Array of selected semester codes
  - SP26
  - FA26
defaultsemester: SP26          # Default semester to display at /courses
course_prefixes: PSYCH,ECON    # Comma-separated course prefixes
```

---

## Service Reference

### as_courses.api

**Service**: `\Drupal\as_courses\Service\CoursesApiService`

**Methods**:
- `getCoursesBySubject($semester, $subjects)` - Fetch courses by semester and subject codes
- `getCoursesByInstructor($semester, $netid)` - Fetch courses by semester and instructor NetID

**Example**:
```php
$courses_api = \Drupal::service('as_courses.api');
$courses = $courses_api->getCoursesBySubject('SP26', 'PSYCH,ECON');
```

### as_courses.semester_generator

**Service**: `\Drupal\as_courses\Service\SemesterGeneratorService`

**Methods**:
- `getSemesterOptions()` - Get semester options for form elements
- `getSemestersBetween($start_year, $end_year)` - Get semesters between specific years
- `getCurrentSemester()` - Get current semester code based on date
- `formatSemesterName($semester_code)` - Format semester code to readable name

**Example**:
```php
$semester_gen = \Drupal::service('as_courses.semester_generator');
$current = $semester_gen->getCurrentSemester(); // Returns 'SP26'
$name = $semester_gen->formatSemesterName('SP26'); // Returns 'Spring 2026'
```

---

## Contributors

- Refactored to OOP architecture: 2026-03-04
- Original procedural version: [See git history]
