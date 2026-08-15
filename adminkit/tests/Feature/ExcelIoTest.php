<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Excel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelIoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->firstOrFail();
    }

    private function assertXlsx(string $url): void
    {
        $response = $this->actingAs($this->admin())->get($url);
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $rows = Excel::rows($path);

        $this->assertNotEmpty($rows, "Berkas kosong: {$url}");
        unlink($path);
    }

    public function test_semua_ekspor_dan_template_menghasilkan_xlsx(): void
    {
        foreach ([
            '/users/export',
            '/permissions/export',
            '/audit-trail/export',
            '/users/import/template',
            '/roles/import/template',
        ] as $url) {
            $this->assertXlsx($url);
        }
    }

    public function test_impor_pengguna_dari_xlsx(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Nama Lengkap', 'Nama Pengguna', 'Alamat Email', 'Nomor HP', 'Peranan', 'Kata Sandi'],
            ['Uji Impor Excel', 'ujiimporxlsx', 'ujiimporxlsx@example.com', '081999000111', 'Super Admin', 'password'],
            ['', '', '', '', '', ''],
        ], null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $this->actingAs($this->admin())
            ->post('/users/import', ['file' => new UploadedFile($path, 'impor.xlsx', null, null, true)])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['username' => 'ujiimporxlsx']);
        User::where('username', 'ujiimporxlsx')->delete();
    }

    public function test_impor_peranan_dari_xlsx(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([['Nama Peranan'], ['Peranan Uji Excel']], null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $this->actingAs($this->admin())
            ->post('/roles/import', ['file' => new UploadedFile($path, 'peranan.xlsx', null, null, true)])
            ->assertRedirect();

        $this->assertDatabaseHas('roles', ['name' => 'Peranan Uji Excel']);
    }

    public function test_berkas_csv_ditolak(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($path, "name\nUji CSV\n");

        $this->actingAs($this->admin())
            ->post('/users/import', ['file' => new UploadedFile($path, 'impor.csv', 'text/csv', null, true)])
            ->assertSessionHasErrors('file');
    }
}
