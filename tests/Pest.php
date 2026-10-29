<?php

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\Username;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPOpenSourceSaver\JWTAuth\JWT;
use Tests\Support\InMemoryUserRepository;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->use(MockeryPHPUnitIntegration::class)
    ->in('Unit');

/**
 * Issues a JWT for the given user without leaving it authenticated, so the
 * next request authenticates from its own Authorization header.
 */
function jwtFor(UserModel $user): string
{
    $token = auth('api')->login($user);

    resetAuth();

    return $token;
}

/**
 * Tests run every request in the same process, unlike production. Forget the
 * resolved guards and the token cached by the JWT instance the guard uses.
 */
function resetAuth(): void
{
    auth()->forgetGuards();
    app(JWT::class)->unsetToken();
}

function storedUserIn(InMemoryUserRepository $users, bool $verified = false): User
{
    $user = User::register(
        new Username('John Doe'),
        new Email('john@example.com'),
        new HashedPassword('hashed-password'),
    );

    if ($verified) {
        $user->verifyEmail(new DateTimeImmutable);
    }

    return $users->save($user);
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/
