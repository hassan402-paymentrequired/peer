# Implementation Plan

- [x]   1. Update database schema for phone-based authentication
    - Create migration to make email field nullable and remove unique constraint
    - Ensure phone field has unique constraint and proper indexing
    - Update password_reset_tokens table to use phone instead of email
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 5.1, 5.2, 5.3_

- [x]   2. Update User model for phone authentication
    - Update fillable fields to properly handle nullable email
    - Ensure authentication identifier methods use phone
    - Update user factory for testing with phone numbers
    - _Requirements: 5.4, 6.3_

- [x]   3. Create phone-based registration system

- [x] 3.1 Update RegisteredUserController validation and logic
    - Modify validation rules to require phone and make email optional
    - Update user creation logic to handle nullable email
    - Implement phone number format validation for Nigerian numbers
    - _Requirements: 1.1, 1.2, 1.5, 1.6_

- [x] 3.2 Create PhoneRegistrationRequest form request class
    - Implement phone number validation with Nigerian format
    - Add optional email validation
    - Include password confirmation validation
    - _Requirements: 1.2, 1.5, 6.1, 6.2_

- [ ]\* 3.3 Write unit tests for registration validation
    - Test phone number format validation
    - Test optional email handling
    - Test duplicate phone number prevention
    - _Requirements: 1.2, 1.5, 5.2_

- [x]   4. Update phone-based password reset system

- [x] 4.1 Update PasswordResetLinkController for phone numbers
    - Modify validation to accept phone instead of email
    - Update password reset link generation to use phone
    - Integrate with SMS service for sending reset links
    - _Requirements: 3.1, 3.2, 6.4_

- [x] 4.2 Update NewPasswordController for phone-based tokens
    - Modify validation to use phone instead of email
    - Update password reset logic to work with phone-based tokens
    - Update success/error messages to reference phone numbers
    - _Requirements: 3.3, 3.4, 6.4_

- [x] 4.3 Create PhonePasswordResetRequest form request class
    - Implement phone number validation for reset requests
    - Add proper error messaging for phone-based resets
    - _Requirements: 3.1, 6.2, 6.4_

- [ ]\* 4.4 Write unit tests for password reset flow
    - Test phone-based password reset token generation
    - Test SMS delivery for password reset
    - Test password reset completion with phone verification
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x]   5. Update frontend authentication forms

- [x] 5.1 Update registration form component
    - Replace email field with phone number input as primary field
    - Add optional email field
    - Implement client-side phone number validation
    - Update form submission to handle phone-based registration
    - _Requirements: 1.1, 1.5, 6.1_

- [x] 5.2 Update login form component
    - Replace email field with phone number input
    - Update form validation for phone number format
    - Update error message handling for phone-based authentication
    - _Requirements: 2.1, 2.3, 6.1, 6.4_

- [x] 5.3 Update forgot password form component
    - Replace email field with phone number input
    - Update form validation and submission logic
    - Update success messages to reference SMS delivery
    - _Requirements: 3.1, 6.1, 6.4_

- [x] 5.4 Update reset password form component
    - Update form to handle phone-based reset tokens
    - Modify validation and error handling
    - Update success/error messages for phone-based flow
    - _Requirements: 3.3, 6.4_

- [ ]\* 5.5 Write frontend component tests
    - Test phone number input validation
    - Test form submission with phone data
    - Test error message display for phone-based authentication
    - _Requirements: 6.1, 6.2, 6.4_

- [x]   6. Update authentication middleware and guards

- [x] 6.1 Verify authentication guard configuration
    - Ensure Laravel auth guards work with phone-based authentication
    - Update any custom authentication logic to use phone lookup
    - Test session management with phone-based authentication
    - _Requirements: 2.4, 5.4, 6.3_

- [x] 6.2 Update rate limiting for phone-based authentication
    - Modify rate limiting keys to use phone numbers instead of email
    - Implement SMS-specific rate limiting for verification codes
    - Update throttling logic in LoginRequest and other auth requests
    - _Requirements: 2.3, 3.2_

- [ ]\* 6.3 Write integration tests for authentication flow
    - Test complete registration flow with phone verification
    - Test login flow with phone number authentication
    - Test password reset flow via SMS
    - Test rate limiting with phone numbers
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.4, 3.1, 3.2, 3.3, 3.4_

- [x]   7. Update notification system for SMS integration

- [x] 7.1 Verify SMS notification configuration
    - Ensure SMS service configuration is properly set up

    - Test SMS delivery for verification codes
    - Implement proper error handling for SMS failures
    - _Requirements: 1.3, 3.2_

- [x] 7.2 Update notification templates for phone-based messaging
    - Create SMS templates for phone verification
    - Create SMS templates for password reset
    - Update notification routing to prioritize SMS over email
    - _Requirements: 1.3, 1.4, 3.2_

- [ ]\* 7.3 Write tests for SMS notification system
    - Test SMS delivery for verification codes
    - Test SMS delivery for password reset links
    - Test notification routing with phone numbers
    - _Requirements: 1.3, 1.4, 3.2_

- [x]   8. Run database migrations and verify data integrity

- [x] 8.1 Execute database migrations in correct order
    - Run migration to update users table schema
    - Run migration to update password_reset_tokens table
    - Verify all existing data remains intact
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 5.1_

- [x] 8.2 Verify authentication system functionality
    - Test user registration with phone numbers
    - Test user login with phone numbers
    - Test password reset with phone numbers
    - Verify email field is properly nullable
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 2.1, 2.2, 2.4, 3.1, 3.2, 3.3, 3.4, 4.3, 4.4, 5.2, 5.3_
