<?php

namespace Georgeff\Problem\Test;

use PHPUnit\Framework\TestCase;
use Georgeff\Problem\ValidationExceptionBuilder;
use Georgeff\Problem\Exception\ValidationException;

class ValidationExceptionBuilderTest extends TestCase
{
    public function test_new_creates_validation_exception_builder(): void
    {
        $this->assertInstanceOf(ValidationExceptionBuilder::class, ValidationExceptionBuilder::new());
    }

    public function test_new_defaults_severity_to_warning(): void
    {
        $exception = ValidationExceptionBuilder::new()->build();

        $this->assertSame('warning', $exception->severity);
    }

    public function test_builds_validation_exception_instance(): void
    {
        $exception = ValidationExceptionBuilder::new()->build();

        $this->assertInstanceOf(ValidationException::class, $exception);
    }

    public function test_throw_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        ValidationExceptionBuilder::new()->throw();
    }

    // --- hasErrors / getErrors ---

    public function test_has_errors_returns_false_initially(): void
    {
        $this->assertFalse(ValidationExceptionBuilder::new()->hasErrors());
    }

    public function test_has_errors_returns_true_after_adding_field(): void
    {
        $builder = ValidationExceptionBuilder::new()->required('email');

        $this->assertTrue($builder->hasErrors());
    }

    public function test_get_errors_returns_empty_array_initially(): void
    {
        $this->assertSame([], ValidationExceptionBuilder::new()->getErrors());
    }

    public function test_get_errors_returns_added_errors(): void
    {
        $builder = ValidationExceptionBuilder::new()
            ->required('email')
            ->required('name');

        $this->assertCount(2, $builder->getErrors());
    }

    // --- auto-generated messages ---

    public function test_build_with_no_errors_uses_generic_message(): void
    {
        $exception = ValidationExceptionBuilder::new()->build();

        $this->assertSame('Validation Error', $exception->getMessage());
    }

    public function test_build_with_one_error_names_the_field(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->required('email')
            ->build();

        $this->assertSame('Validation failed for field [email]', $exception->getMessage());
    }

    public function test_build_with_multiple_errors_shows_count(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->required('email')
            ->required('name')
            ->build();

        $this->assertSame('Validation failed for 2 field(s)', $exception->getMessage());
    }

    public function test_explicit_message_is_not_overridden(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->message('Custom validation error')
            ->required('email')
            ->build();

        $this->assertSame('Custom validation error', $exception->getMessage());
    }

    // --- field ---

    public function test_field_adds_error_entry(): void
    {
        $builder = ValidationExceptionBuilder::new()->field('email', 'email', 'not-an-email');

        $errors = $builder->getErrors();

        $this->assertCount(1, $errors);
        $this->assertSame('email', $errors[0]['field']);
        $this->assertSame('email', $errors[0]['rule']);
        $this->assertSame('not-an-email', $errors[0]['value']);
    }

    public function test_field_adds_errors_to_context(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->field('email', 'email', 'bad')
            ->build();

        $this->assertArrayHasKey('errors', $exception->context);
        $this->assertCount(1, $exception->context['errors']);
    }

    public function test_field_value_defaults_to_null(): void
    {
        $builder = ValidationExceptionBuilder::new()->field('name', 'required');

        $this->assertNull($builder->getErrors()[0]['value']);
    }

    // --- required ---

    public function test_required_sets_required_rule(): void
    {
        $builder = ValidationExceptionBuilder::new()->required('name');
        $errors  = $builder->getErrors();

        $this->assertSame('name', $errors[0]['field']);
        $this->assertSame('required', $errors[0]['rule']);
        $this->assertNull($errors[0]['value']);
    }

    // --- minLength / maxLength ---

    public function test_min_length_adds_error_and_constraint(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->minLength('username', 'ab', 3)
            ->build();

        $errors = $exception->context['errors'];
        $this->assertSame('min_length', $errors[0]['rule']);
        $this->assertSame(3, $exception->context['username_min_length']);
    }

    public function test_max_length_adds_error_and_constraint(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->maxLength('username', 'toolongvalue', 10)
            ->build();

        $errors = $exception->context['errors'];
        $this->assertSame('max_length', $errors[0]['rule']);
        $this->assertSame(10, $exception->context['username_max_length']);
    }

    // --- email / url / numeric / integer / boolean / array ---

    public function test_email_sets_email_rule(): void
    {
        $builder = ValidationExceptionBuilder::new()->email('email', 'bad');

        $this->assertSame('email', $builder->getErrors()[0]['rule']);
    }

    public function test_url_sets_url_rule(): void
    {
        $builder = ValidationExceptionBuilder::new()->url('website', 'bad');

        $this->assertSame('url', $builder->getErrors()[0]['rule']);
    }

    public function test_numeric_sets_numeric_rule(): void
    {
        $builder = ValidationExceptionBuilder::new()->numeric('age', 'abc');

        $this->assertSame('numeric', $builder->getErrors()[0]['rule']);
    }

    public function test_integer_sets_integer_rule(): void
    {
        $builder = ValidationExceptionBuilder::new()->integer('count', '1.5');

        $this->assertSame('integer', $builder->getErrors()[0]['rule']);
    }

    public function test_boolean_sets_boolean_rule(): void
    {
        $builder = ValidationExceptionBuilder::new()->boolean('active', 'yes');

        $this->assertSame('boolean', $builder->getErrors()[0]['rule']);
    }

    public function test_array_sets_array_rule(): void
    {
        $builder = ValidationExceptionBuilder::new()->array('tags', 'not-an-array');

        $this->assertSame('array', $builder->getErrors()[0]['rule']);
    }

    // --- regex ---

    public function test_regex_adds_error_and_pattern(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->regex('slug', 'bad slug!', '/^[a-z0-9-]+$/')
            ->build();

        $this->assertSame('regex', $exception->context['errors'][0]['rule']);
        $this->assertSame('/^[a-z0-9-]+$/', $exception->context['slug_regex_pattern']);
    }

    // --- min / max ---

    public function test_min_adds_error_and_constraint(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->min('age', 15, 18)
            ->build();

        $this->assertSame('min', $exception->context['errors'][0]['rule']);
        $this->assertSame(18, $exception->context['age_min']);
    }

    public function test_max_adds_error_and_constraint(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->max('age', 200, 120)
            ->build();

        $this->assertSame('max', $exception->context['errors'][0]['rule']);
        $this->assertSame(120, $exception->context['age_max']);
    }

    // --- between ---

    public function test_between_adds_single_error_entry(): void
    {
        $builder = ValidationExceptionBuilder::new()->between('age', 5, 18, 65);

        $this->assertCount(1, $builder->getErrors());
    }

    public function test_between_sets_between_rule_with_bounds(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->between('age', 5, 18, 65)
            ->build();

        $this->assertSame('between', $exception->context['errors'][0]['rule']);
        $this->assertSame(18, $exception->context['age_min']);
        $this->assertSame(65, $exception->context['age_max']);
    }

    // --- date ---

    public function test_date_adds_error_and_format(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->date('dob', '32-13-2000', 'Y-m-d')
            ->build();

        $this->assertSame('date', $exception->context['errors'][0]['rule']);
        $this->assertSame('Y-m-d', $exception->context['dob_date_format']);
    }

    // --- in / notIn ---

    public function test_in_adds_error_and_allowed_values(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->in('status', 'unknown', ['active', 'inactive'])
            ->build();

        $this->assertSame('in', $exception->context['errors'][0]['rule']);
        $this->assertSame(['active', 'inactive'], $exception->context['status_allowed']);
    }

    public function test_not_in_adds_error_and_disallowed_values(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->notIn('role', 'admin', ['admin', 'superuser'])
            ->build();

        $this->assertSame('notIn', $exception->context['errors'][0]['rule']);
        $this->assertSame(['admin', 'superuser'], $exception->context['role_disallowed']);
    }

    // --- multiple fields ---

    public function test_multiple_fields_accumulate_in_errors(): void
    {
        $exception = ValidationExceptionBuilder::new()
            ->required('name')
            ->email('email', 'bad')
            ->minLength('password', 'abc', 8)
            ->build();

        $this->assertCount(3, $exception->context['errors']);
    }

    public function test_errors_in_context_stay_in_sync_with_get_errors(): void
    {
        $builder = ValidationExceptionBuilder::new()
            ->required('name')
            ->required('email');

        $exception = $builder->build();

        $this->assertSame($builder->getErrors(), $exception->context['errors']);
    }
}
