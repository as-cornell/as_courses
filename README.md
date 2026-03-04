[![Latest Stable Version](https://poser.pugx.org/as-cornell/as_courses/v)](https://packagist.org/packages/as-cornell/as_courses)
# AS COURSES (as_courses)

## INTRODUCTION

A Drupal module that provides lists of courses pulled from the Cornell course roster API (classes.cornell.edu) by subject and semester. Built with service-oriented architecture and dependency injection for Drupal 10/11 best practices.

### Features

- **Dynamic Semester Generation** - Semesters auto-generate based on current year (no more manual updates!)
- **Service-Based API Client** - Proper HTTP client with error handling and logging
- **Twig Functions** - Multiple Twig functions for displaying course data in templates
- **Courses Controller** - Route to display all courses for selected subjects/semesters
- **Settings Form** - Configure semesters, subjects, and defaults via admin UI
- **Fully Testable** - Comprehensive unit and functional tests
- **Cached Responses** - API responses cached for performance (configurable duration)

### Architecture (v2.0+)

- **Services**: Core business logic in injectable services
  - `CoursesApiService` - Cornell Classes API client
  - `SemesterGeneratorService` - Dynamic semester list generation
- **Dependency Injection**: All components use proper DI (no static calls)
- **Error Handling**: Comprehensive logging via dedicated logger channel
- **Configuration**: Schema-based configuration with install defaults

## REQUIREMENTS

- Drupal 10.0 or higher
- PHP 8.1 or higher
- Internet access to Cornell Classes API (classes.cornell.edu)

## INSTALLATION

Install as you would normally install a contributed Drupal module. See
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules)
for further information.

```bash
# Via Composer (if published to Packagist)
composer require as-cornell/as_courses

# Or install manually
# 1. Download/clone to web/modules/custom/as_courses
# 2. Enable the module
drush en as_courses -y
```

## CONFIGURATION

### Admin Settings

Configure the module at: `/admin/config/content/as_courses`

**Settings**:
- **Semesters to display** - Select which semesters appear in tabs (checkboxes)
- **Default semester** - Which semester loads at `/courses` route
- **Course Prefixes** - Comma-separated list (e.g., `PSYCH,ECON,HIST`)

### Advanced Configuration

Edit `/admin/config/development/configuration` or add to `settings.php`:

```php
// Adjust semester generation range
$config['as_courses.settings']['semester_start_year_offset'] = -1;  // Current year - 1
$config['as_courses.settings']['semester_end_year_offset'] = 5;     // Current year + 5

// API timeout (seconds)
$config['as_courses.settings']['api_timeout'] = 10;

// Cache duration (seconds)
$config['as_courses.settings']['cache_duration'] = 3600;  // 1 hour
```

## USAGE

### Twig Functions

All Twig functions available in templates:

**1. Display Courses by Subject**
```twig
{# Get courses for Spring 2026, PSYCH subject, show 5 courses, random order #}
{% set courses = parse_courses_json('SP26', 'PSYCH', 5, 'random') %}
{% for course in courses %}
  <h3>{{ course.subject }} {{ course.number }}: {{ course.title }}</h3>
  <p>{{ course.description }}</p>
{% endfor %}
```

**2. Display Courses by Instructor (NetID)**
```twig
{# Get courses taught by NetID abc123 in Fall 2026 #}
{% set courses = parse_person_courses_netid_json('FA26', 'abc123') %}
{% for course in courses %}
  <div>{{ course.subject }} {{ course.number }}: {{ course.title }}</div>
{% endfor %}
```

**3. Get Current Semesters**
```twig
{# Get list of selected semesters from admin settings #}
{% set semesters = get_current_semesters() %}
{% for code in semesters %}
  <a href="/courses/{{ code }}">{{ parse_semester_name(code) }}</a>
{% endfor %}
```

**4. Format Semester Names**
```twig
{# Convert semester code to readable name #}
{{ parse_semester_name('SP26') }}  {# Output: Spring 2026 #}
{{ parse_semester_name('FA25') }}  {# Output: Fall 2025 #}
```

**5. Person's Courses by Subject**
```twig
{# Get courses for NetID xyz456 in PSYCH subject, Spring 2026 #}
{% set courses = parse_person_courses_json('SP26', 'PSYCH', 'xyz456') %}
```

**6. Person's Courses Filtered by Subjects**
```twig
{# Get NetID courses filtered to specific subject list #}
{% set subjects = ['PSYCH', 'ECON'] %}
{% set courses = parse_person_courses_netid_prefix_json('SP26', subjects, 'abc123') %}
```

### Using Services in Custom Code

**API Service**:
```php
// Get courses by subject
$courses_api = \Drupal::service('as_courses.api');
$courses = $courses_api->getCoursesBySubject('SP26', 'PSYCH,ECON');

// Get courses by instructor
$courses = $courses_api->getCoursesByInstructor('FA25', 'abc123');
```

**Semester Generator Service**:
```php
// Get semester options for forms
$semester_gen = \Drupal::service('as_courses.semester_generator');
$options = $semester_gen->getSemesterOptions();
// Returns: ['FA25' => 'Fall 2025', 'WI26' => 'Winter 2026', ...]

// Get current semester
$current = $semester_gen->getCurrentSemester();
// Returns: 'SP26' (based on current date)

// Format semester code
$name = $semester_gen->formatSemesterName('SP26');
// Returns: 'Spring 2026'

// Get custom range
$semesters = $semester_gen->getSemestersBetween(2025, 2027);
```

### Controller Route

Visit `/courses` or `/courses/{semester}` to see the default courses page (uses `courses.html.twig` template).

Example:
- `/courses` - Displays default semester
- `/courses/SP26` - Displays Spring 2026 courses
- `/courses/FA25` - Displays Fall 2025 courses

## TESTING

### Run Unit Tests

```bash
# From Drupal root
vendor/bin/phpunit web/modules/custom/as_courses/tests/src/Unit

# Specific test
vendor/bin/phpunit web/modules/custom/as_courses/tests/src/Unit/CoursesApiServiceTest.php
```

### Run Functional Tests

```bash
vendor/bin/phpunit web/modules/custom/as_courses/tests/src/Functional
```

### Manual Testing Checklist

- [ ] Visit `/admin/config/content/as_courses` - Form loads with dynamic semesters
- [ ] Save settings form - No errors, config saves
- [ ] Visit `/courses` - Page loads with default semester
- [ ] Visit `/courses/SP26` - Page loads with specified semester
- [ ] Use Twig functions in template - Course data displays correctly
- [ ] Check logs: `drush watchdog:show --type=as_courses` - API calls logged
- [ ] Check cache: Course data cached properly

## TROUBLESHOOTING

### No Courses Appearing

1. **Check API connectivity**:
   ```bash
   curl "https://classes.cornell.edu/api/2.0/search/classes.json?roster=SP26&subject=PSYCH"
   ```

2. **Check module logs**:
   ```bash
   drush watchdog:show --type=as_courses --count=50
   ```

3. **Clear cache**:
   ```bash
   drush cr
   ```

4. **Verify configuration**:
   ```bash
   drush config:get as_courses.defaults
   drush config:get as_courses.settings
   ```

### Semester List Not Updating

- Dynamic semester generation added in v2.0.0
- Clear cache: `drush cr`
- Adjust year range in `as_courses.settings` config

### Deprecated Function Warnings

If you see deprecation warnings:
```
as_courses_get_courses_json() is deprecated in as_courses:2.0.0...
```

Update your code to use the service:
```php
// Old (deprecated)
$courses = as_courses_get_courses_json($semester, $subjects);

// New (v2.0+)
$courses = \Drupal::service('as_courses.api')->getCoursesBySubject($semester, $subjects);
```

## CHANGELOG

See [CHANGELOG.md](CHANGELOG.md) for version history and upgrade notes.

## MAINTAINERS

Current maintainers for Drupal 10/11:

- Mark Wilson (markewilson)

### Contributing

- Report bugs and feature requests in the issue queue
- Follow Drupal coding standards
- Include tests with code changes
- See [CHANGELOG.md](CHANGELOG.md) for architecture details
