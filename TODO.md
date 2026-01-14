# TODO: Enable Login Testing with Dummy Data

## Completed Tasks

- [x] Update UserFactory to include phone and user_type for realistic dummy data
- [x] Update DatabaseSeeder to create specific dummy users (admin@test.com, student@test.com, teacher@test.com) with known credentials
- [x] Create LoginTest.php feature test with tests for login via email, phone, wrong credentials, and validation

## Next Steps

- [x] Run the test suite to verify login testing works: `php artisan test tests/Feature/Auth/LoginTest.php`
- [ ] Note: Test execution blocked by PHP version requirement (>=8.2.0), current is 8.1.10. Upgrade PHP to run tests.
- [ ] Run the database seeder to populate DB with dummy data: `php artisan db:seed` (after PHP upgrade)
- [ ] Optionally, test login manually in browser using dummy credentials (e.g., admin@test.com / password)
- [ ] If 2FA is triggered in tests, adjust the test to handle it (currently tests assume no 2FA for low-risk logins)
