import gc
import json
import os
import subprocess
import sys
import traceback
from dataclasses import asdict

from pdf_reader import PdfReader
from excel_writer import ExcelWriter
from models import FeedbackResult, FeedbackRow

DEFAULT_CHUNK_SIZE = 10


def merge_result(destination: FeedbackResult, source: FeedbackResult):
    """
    Menggabungkan hasil pembacaan beberapa PDF.
    """

    destination.ri.rows.extend(source.ri.rows)
    destination.rj.rows.extend(source.rj.rows)


def run_worker(args):
    """
    Mode worker (dipanggil oleh read_isolated):

        python extract_feedback.py --chunk file.pdf <start> <end>

    Mencetak JSON berisi baris untuk halaman [start, end).
    Stdout HANYA boleh berisi JSON.
    """

    pdf_path, start, end = args

    rows = PdfReader().read_chunk(pdf_path, int(start), int(end))

    sys.stdout.write(json.dumps([asdict(r) for r in rows]))


def read_isolated(
    reader: PdfReader,
    pdf_path: str,
    chunk_size: int
) -> FeedbackResult:
    """
    Tiap chunk dibaca di PROSES PYTHON TERPISAH. Setelah proses selesai,
    seluruh memorinya dikembalikan ke OS, jadi puncak RAM tidak akan melebihi
    kebutuhan satu chunk (lebih aman di shared hosting, sedikit lebih lambat).
    """

    pelayanan, total = reader.inspect(pdf_path)

    result = FeedbackResult()

    for start in range(0, total, chunk_size):

        end = min(start + chunk_size, total)

        proc = subprocess.run(
            [
                sys.executable,
                os.path.abspath(__file__),
                "--chunk",
                pdf_path,
                str(start),
                str(end),
            ],
            capture_output=True,
            text=True,
        )

        if proc.returncode != 0:

            hint = ""

            if proc.returncode in (-9, 137):
                hint = " (proses di-KILL: kemungkinan batas memori, perkecil chunk size)"

            raise Exception(
                f"Chunk halaman {start + 1}-{end} pada {pdf_path} gagal, "
                f"kode {proc.returncode}{hint}. {proc.stderr.strip()}"
            )

        for item in json.loads(proc.stdout or "[]"):
            reader.add(result, pelayanan, FeedbackRow(**item))

    return result


def main():

    #
    # Format:
    #
    # python extract_feedback.py [--isolate] [--chunk-size=N]
    #     file1.pdf
    #     file2.pdf
    #     output.xlsx
    #

    isolate = "--isolate" in sys.argv

    chunk_size = DEFAULT_CHUNK_SIZE

    for arg in sys.argv:
        if arg.startswith("--chunk-size="):
            chunk_size = max(1, int(arg.split("=", 1)[1]))

    args = [a for a in sys.argv[1:] if not a.startswith("--")]

    if len(args) < 2:
        raise Exception(
            "Parameter tidak lengkap."
        )

    pdf_files = args[:-1]

    output_file = args[-1]

    reader = PdfReader()

    result = FeedbackResult()

    for pdf in pdf_files:

        if isolate:
            current = read_isolated(reader, pdf, chunk_size)
        else:
            current = reader.read(pdf, chunk_size)

        merge_result(result, current)

        del current

        gc.collect()

    writer = ExcelWriter()

    writer.write(
        result,
        output_file
    )

    print(output_file)


if __name__ == "__main__":

    try:

        if len(sys.argv) > 1 and sys.argv[1] == "--chunk":
            run_worker(sys.argv[2:])
        else:
            main()

        sys.exit(0)

    except Exception as e:

        # Sama seperti kode asli: pesan error ke STDOUT, traceback ke STDERR.
        # Khusus mode worker (--chunk), stdout dicadangkan untuk JSON sehingga
        # pesan error dikirim ke stderr.
        is_worker = len(sys.argv) > 1 and sys.argv[1] == "--chunk"

        print(str(e), file=sys.stderr if is_worker else sys.stdout)

        traceback.print_exc()

        sys.exit(1)