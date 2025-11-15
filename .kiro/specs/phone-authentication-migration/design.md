# Design Document

## Overview

This design outlines the migration from email-based to phone number-based authentication system. The system will use SMS for user verification and notifications while maintaining email as an optional field for future features. The migration involves database schema changes, authentication controller updates, form validation modifications, and frontend form updates.

## Architecture

### Authentication Flow Changes

The authentication system will be restructured around phone numbers as the primary identifier:

1. **Registration Flow**: Phone number → SMS verification → Account creation
2. **Login Flow**: Phone number + password → Authentication
3. **Password Reset Flow**: Phone number → SMS reset link → Password update
4. **Verification Flow**: SMS-based phone verification instead of email verification

### Database Schema Changes

The existing database schema requires modifications to support phone-based authentication:

- Make `email` field nullable and remove unique constraint
- Ensure `phone` field has unique constraint and proper validation
- Update password reset tokens table to use phone numbers
- Add phone verification tracking

## Components and Interfaces

### Database Migrations

**Migration 1: Update Users Table Schema**

- Make email field nullable: `$table->string('email')->nullable()->change()`
- Remove unique constraint from email field
- Ensure phone field has unique constraint: `$table->string('phone')->unique()->change()`
- Ensure phone_verified_at field exists

**Migration 2: Update Password Reset Tokens Table**

- Rename `email` column to `phone` in password_reset_tokens table
- Update primary key to use phone instead of email
- Ensure phone field validation matches users table

### Authentication Controllers

**RegisteredUserController Updates**

- Update validation rules to require phone instead of email
- Make email validation optional
- Add phone number format validation (Nigerian format: `^(\+234|234|0)[789][01]\d{8}$`)
- Update user creation to use phone as primary identifier
- Implement SMS verification instead of email verification

**AuthenticatedSessionController Updates**

- Already updated to use phone-based authentication (LoginRequest shows phone validation)
- Ensure consistent phone number handling

**Password Reset Controllers Updates**

- Update PasswordResetLinkController to accept phone numbers
- Modify validation to use phone instead of email
- Update SMS sending logic for password reset
- Update NewPasswordController to handle phone-based reset tokens

### Form Request Classes

**LoginRequest Updates**

- Already implemented with phone validation
- Ensure consistent phone format validation across all requests

**New PhoneRegistrationRequest**

- Phone number validation with Nigerian format
- Optional email validation
- Password validation with confirmation
- Name validation

**New PhonePasswordResetRequest**

- Phone number validation
- Integration with SMS service for reset links

### User Model Updates

**Authentication Identifier Changes**

- Update `getAuthIdentifierName()` to return 'phone' (already implemented)
- Ensure `findForAuth()` method uses phone lookup (already implemented)
- Update fillable fields to include phone as required

**Notification Routing**

- Ensure `routeNotificationForSms()` returns phone number (already implemented)
- Update notification preferences to use SMS as primary channel

### Frontend Components

**Authentication Forms**

- Update login form to use phone input instead of email
- Update registration form to use phone as primary field, email as optional
- Update forgot password form to use phone input
- Update reset password form to handle phone-based tokens

**Form Validation**

- Implement client-side phone number validation
- Update error message handling for phone-based authentication
- Add phone number formatting helpers

## Data Models

### User Model Schema

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('phone')->unique();
    $table->timestamp('phone_verified_at')->nullable();
    $table->string('email')->nullable()->change(); // Remove unique constraint
    $table->timestamp('email_verified_at')->nullable();
});
```

### Password Reset Tokens Schema

```php
Schema::table('password_reset_tokens', function (Blueprint $table) {
    $table->dropPrimary(['email']);
    $table->renameColumn('email', 'phone');
    $table->primary('phone');
});
```

### Validation Rules

- Phone: `required|string|regex:/^(\+234|234|0)[789][01]\d{8}$/|unique:users,phone`
- Email: `nullable|string|email|max:255`
- Password: Standard Laravel password rules with confirmation

## Error Handling

### Validation Errors

- Phone format validation with clear error messages
- Duplicate phone number registration prevention
- SMS delivery failure handling
- Rate limiting for SMS verification attempts

### Authentication Errors

- Invalid phone number format errors
- Phone number not found errors
- SMS verification timeout handling
- Password reset token expiration

### Migration Errors

- Handle existing users with duplicate emails
- Ensure data integrity during schema changes
- Rollback procedures for failed migrations

## Testing Strategy

### Unit Tests

- Phone number validation rules testing
- User model authentication method testing
- SMS notification routing testing
- Password reset token generation with phone numbers

### Integration Tests

- Complete registration flow with phone verification
- Login flow with phone number authentication
- Password reset flow via SMS
- Database migration testing with existing data

### Frontend Tests

- Form validation for phone number inputs
- Error message display for phone-based authentication
- SMS verification code input handling
- Form submission with phone data

## Implementation Considerations

### SMS Service Integration

- Ensure SMS service is properly configured for verification codes
- Implement rate limiting for SMS sending
- Handle SMS delivery failures gracefully
- Store SMS verification codes securely

### Data Migration Strategy

- Backup existing user data before migration
- Handle users who may have duplicate emails
- Ensure phone numbers are properly formatted and validated
- Test migration on staging environment first

### Backward Compatibility

- Maintain email field for future use
- Ensure existing sessions remain valid during migration
- Handle edge cases where users may not have phone numbers

### Security Considerations

- Implement proper rate limiting for phone-based authentication
- Secure SMS verification code generation and validation
- Ensure phone number privacy and data protection
- Implement proper session management with phone-based authentication
