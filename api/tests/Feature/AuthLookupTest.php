<?php

namespace Tests\Feature;

use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Auth\App\Services\AuthService;
use Tests\TestCase;

class AuthLookupTest extends TestCase
{
    private $originalResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalResolver = Model::getConnectionResolver();
    }

    protected function tearDown(): void
    {
        Model::setConnectionResolver($this->originalResolver);
        parent::tearDown();
    }

    private function localRows(array $rows): void
    {
        // Eloquent runs against a recording connection with no PDO or network.
        $connection = new class($rows) extends MySqlConnection {
            public function __construct(private array $rows)
            {
                parent::__construct(null, 'test');
            }

            public function select($query, $bindings = [], $useReadPdo = true)
            {
                return array_map(fn ($row) => (object) $row, $this->rows);
            }
        };
        $resolver = new ConnectionResolver(['local' => $connection]);
        $resolver->setDefaultConnection('local');
        Model::setConnectionResolver($resolver);
    }

    public function test_local_user_is_returned_without_contacting_rahkaran(): void
    {
        $this->localRows([['id' => 12, 'mobile' => '09120000000', 'name' => 'Local user']]);
        DB::shouldReceive('connection')->never();
        $result = app(AuthService::class)->findOrCreateUser('09120000000');
        $this->assertTrue($result['found']);
        $this->assertSame('local', $result['source']);
        $this->assertSame(12, $result['user']->id);
    }

    public function test_missing_local_user_is_looked_up_by_mobile_in_rahkaran(): void
    {
        $this->localRows([]);
        $query = \Mockery::mock();
        $query->shouldReceive('table')->once()->with('HCM3.Employee AS e')->andReturnSelf();
        $query->shouldReceive('join')->once()->with('GNR3.Party AS p', 'p.PartyID', '=', 'e.PartyRef')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('p.Mobile', '09120000000')->andReturnSelf();
        $query->shouldReceive('select')->once()->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturnNull();
        DB::shouldReceive('connection')->once()->with('gtarabar')->andReturn($query);
        $result = app(AuthService::class)->findOrCreateUser('09120000000');
        $this->assertFalse($result['found']);
        $this->assertNull($result['user']);
    }

    public function test_valid_otp_can_be_verified_before_password_reset(): void
    {
        $this->localRows([['id' => 12, 'mobile' => '09120000000', 'otp_code' => '01234',
            'otp_expires_at' => now()->addMinutes(5)->toDateTimeString()]]);
        $this->assertSame(12, app(AuthService::class)->verifyOtp('09120000000', '01234')->id);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $this->localRows([['id' => 12, 'otp_code' => '01234',
            'otp_expires_at' => now()->subMinute()->toDateTimeString()]]);
        $this->expectException(ValidationException::class);
        app(AuthService::class)->verifyOtp('09120000000', '01234');
    }
}
