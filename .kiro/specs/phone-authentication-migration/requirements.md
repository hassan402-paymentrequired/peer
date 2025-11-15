# Requirements Document

## Introduction

This feature migrates the authentication system from email-based to phone number-based authentication while maintaining email as an optional field for future use. The system will use SMS for user verification and notifications instead of email, requiring updates to registration, login, password reset flows, and database schema modifications.

## Requirements

### Requirement 1

**User Story:** As a new user, I want to register using my phone number instead of email, so that I can receive SMS notifications and verify my account via SMS.

#### Acceptance Criteria

1. WHEN a user accesses the registration form THEN the system SHALL display a phone number input field as the primary identifier
2. WHEN a user submits registration with a phone number THEN the system SHALL validate the phone number format
3. WHEN a user submits registration with a valid phone number THEN the system SHALL send an SMS verification code
4. WHEN a user enters the correct SMS verification code THEN the system SHALL mark the phone number as verified
5. IF a user provides an email during registration THEN the system SHALL store it as optional information
6. WHEN a user submits registration without an email THEN the system SHALL still allow successful registration

### Requirement 2

**User Story:** As an existing user, I want to login using my phone number instead of email, so that I can access my account using my primary contact method.

#### Acceptance Criteria

1. WHEN a user accesses the login form THEN the system SHALL display a phone number input field instead of email
2. WHEN a user submits login credentials with phone number THEN the system SHALL authenticate using phone number and password
3. WHEN a user enters an invalid phone number format THEN the system SHALL display appropriate validation errors
4. WHEN a user successfully logs in THEN the system SHALL create a session using the phone-based authentication

### Requirement 3

**User Story:** As a user who forgot my password, I want to reset it using my phone number, so that I can regain access to my account via SMS.

#### Acceptance Criteria

1. WHEN a user requests password reset THEN the system SHALL accept phone number instead of email
2. WHEN a user submits a valid phone number for password reset THEN the system SHALL send an SMS with reset instructions
3. WHEN a user clicks the reset link from SMS THEN the system SHALL allow password reset using phone number verification
4. WHEN a user completes password reset THEN the system SHALL invalidate all existing sessions for that phone number

### Requirement 4

**User Story:** As a system administrator, I want the email field to be nullable in the database, so that we can optionally collect email addresses for future features without breaking existing functionality.

#### Acceptance Criteria

1. WHEN the database migration runs THEN the system SHALL make the email field nullable
2. WHEN the database migration runs THEN the system SHALL remove the unique constraint on email field
3. WHEN a user record is created without email THEN the system SHALL store NULL in the email field
4. WHEN a user record is created with email THEN the system SHALL store the email value normally
5. WHEN querying users THEN the system SHALL handle NULL email values gracefully

### Requirement 5

**User Story:** As a user, I want phone number to be the unique identifier for my account, so that I cannot create duplicate accounts with the same phone number.

#### Acceptance Criteria

1. WHEN the database migration runs THEN the system SHALL add a unique constraint on the phone number field
2. WHEN a user attempts to register with an existing phone number THEN the system SHALL prevent registration and display an error
3. WHEN a user attempts to update their phone number to an existing one THEN the system SHALL prevent the update and display an error
4. WHEN querying users by phone number THEN the system SHALL return exactly one user or none

### Requirement 6

**User Story:** As a developer, I want all authentication forms and validation rules updated to use phone numbers, so that the system consistently uses phone-based authentication throughout.

#### Acceptance Criteria

1. WHEN authentication forms are rendered THEN the system SHALL display phone number fields instead of email fields
2. WHEN form validation runs THEN the system SHALL validate phone number format instead of email format
3. WHEN authentication requests are processed THEN the system SHALL use phone number for user lookup
4. WHEN authentication responses are returned THEN the system SHALL reference phone numbers in success/error messages
5. WHEN password reset tokens are created THEN the system SHALL associate them with phone numbers instead of email addresses
