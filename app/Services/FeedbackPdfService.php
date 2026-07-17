<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class FeedbackPdfService
{
    private string $tempDirectory;

    private string $pythonDirectory;

    public function __construct()
    {
        $this->tempDirectory = storage_path('app/temp/feedback');

        $this->pythonDirectory = base_path('python/feedback');

        File::ensureDirectoryExists($this->tempDirectory);
    }

    /**
     * @param UploadedFile[] $pdfFiles
     */
    public function convertToExcel(array $pdfFiles): string
    {
        if (empty($pdfFiles)) {
            throw new Exception('File feedback belum dipilih.');
        }

        if (count($pdfFiles) > 2) {
            throw new Exception('Maksimal 2 file feedback.');
        }

        $session = Str::uuid()->toString();

        $workingDirectory = $this->tempDirectory . DIRECTORY_SEPARATOR . $session;

        File::ensureDirectoryExists($workingDirectory);

        $pdfPaths = [];

        foreach ($pdfFiles as $i => $file) {

            $filename = 'feedback_' . ($i + 1) . '.pdf';

            $file->move($workingDirectory, $filename);

            $pdfPaths[] = $workingDirectory . DIRECTORY_SEPARATOR . $filename;
        }

        $excelPath = $workingDirectory . DIRECTORY_SEPARATOR . 'feedback.xlsx';

        $this->runPython(
            $pdfPaths,
            $excelPath
        );

        if (!File::exists($excelPath)) {
            throw new Exception('Python tidak menghasilkan file Excel.');
        }

        return $excelPath;
    }

    /**
     * Menjalankan python.
     */
    private function runPython(array $pdfPaths, string $excelPath): void
    {
        $script = $this->pythonDirectory . DIRECTORY_SEPARATOR . 'extract_feedback.py';

        $command = sprintf(
            '"%s" "%s"',
            config('services.python.binary'),
            $script
        );

        foreach ($pdfPaths as $pdf) {
            $command .= ' "' . $pdf . '"';
        }

        $command .= ' "' . $excelPath . '"';

        $output = [];
        $status = 0;

        exec($command . ' 2>&1', $output, $status);

        Log::info(implode(PHP_EOL, $output));

        if ($status !== 0) {
            throw new Exception(
                implode(PHP_EOL, $output)
            );
        }
    }

    /**
     * Menghapus seluruh file temporary hasil konversi.
     */
    public function cleanup(string $excelPath): void
    {
        if (empty($excelPath)) {
            return;
        }

        $directory = dirname($excelPath);

        if (!is_dir($directory)) {
            return;
        }

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') as $file) {

            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($directory);
    }
}
