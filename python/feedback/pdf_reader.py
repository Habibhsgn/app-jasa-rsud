import gc
import re
from datetime import datetime

import pdfplumber

from models import (
    FeedbackResult,
    FeedbackRow,
)


class PdfReader:

    # Jumlah halaman per chunk. Kecil = hemat memori, tapi sedikit lebih lambat.
    CHUNK_SIZE = 10

    # ------------------------------------------------------------------
    # API utama
    # ------------------------------------------------------------------

    def read(
        self,
        pdf_path: str,
        chunk_size: int | None = None
    ) -> FeedbackResult:
        """
        Membaca satu PDF per CHUNK halaman. Setiap chunk membuka PDF dari
        awal lalu menutupnya lagi, sehingga seluruh cache pdfminer/pdfplumber
        dilepas di antara chunk.
        """

        chunk_size = chunk_size or self.CHUNK_SIZE

        pelayanan, total = self.inspect(pdf_path)

        result = FeedbackResult()

        for start in range(0, total, chunk_size):

            end = min(start + chunk_size, total)

            for feedback in self.read_chunk(pdf_path, start, end):
                self.add(result, pelayanan, feedback)

        return result

    def inspect(self, pdf_path: str) -> tuple[str, int]:
        """
        Mengembalikan (jenis pelayanan, jumlah halaman) tanpa membaca tabel.
        """

        with pdfplumber.open(pdf_path) as pdf:

            total = len(pdf.pages)

            pelayanan = self.detect_service(pdf)

        gc.collect()

        if pelayanan is None:
            raise Exception(
                f"Tingkat Pelayanan tidak ditemukan pada file : {pdf_path}"
            )

        return pelayanan, total

    def read_chunk(
        self,
        pdf_path: str,
        start: int,
        end: int
    ) -> list[FeedbackRow]:
        """
        Membaca halaman [start, end) (index mulai 0) dan mengembalikan
        baris-baris feedback yang ditemukan.
        """

        rows: list[FeedbackRow] = []

        with pdfplumber.open(pdf_path) as pdf:

            for index in range(start, min(end, len(pdf.pages))):

                page = pdf.pages[index]

                try:

                    for table in page.extract_tables() or []:

                        for row in table:

                            feedback = self.parse_row(row)

                            if feedback is not None:
                                rows.append(feedback)

                finally:

                    self.release(page)

                    del page

        gc.collect()

        return rows

    def add(
        self,
        result: FeedbackResult,
        pelayanan: str,
        feedback: FeedbackRow
    ):

        if pelayanan == "RI":
            result.ri.add(feedback)
        else:
            result.rj.add(feedback)

    # ------------------------------------------------------------------
    # Helper
    # ------------------------------------------------------------------

    def release(self, page):
        """
        Melepas memori yang ditahan pdfplumber untuk satu halaman.
        """

        if hasattr(page, "flush_cache"):
            page.flush_cache()

        if hasattr(page, "close"):
            try:
                page.close()
            except Exception:
                pass

    def detect_service(self, pdf) -> str | None:
        """
        Mendeteksi apakah file RITL atau RJTL.
        """

        for index in range(min(2, len(pdf.pages))):

            page = pdf.pages[index]

            try:
                text = page.extract_text() or ""
            finally:
                self.release(page)

            if "RITL" in text:
                return "RI"

            if "RJTL" in text:
                return "RJ"

        return None

    def parse_row(
        self,
        row
    ) -> FeedbackRow | None:

        if row is None:
            return None

        row = [self.clean(cell) for cell in row]

        if len(row) < 6:
            return None

        if not self.is_number(row[0]):
            return None

        if not self.is_sep(row[1]):
            return None

        if not self.is_date(row[2]):
            return None

        return FeedbackRow(
            no=int(row[0]),
            no_sep=row[1],
            tgl_verifikasi=row[2],
            biaya_riil_rs=self.to_number(row[3]),
            biaya_diajukan=self.to_number(row[4]),
            biaya_disetujui=self.to_number(row[5]),
        )

    def clean(self, value) -> str:

        if value is None:
            return ""

        return str(value).replace("\n", " ").strip()

    def is_number(self, value) -> bool:
        return value.isdigit()

    def is_sep(self, value) -> bool:
        return bool(re.match(r'^\d+[A-Z]\d+', value))

    def is_date(self, value) -> bool:

        try:
            datetime.strptime(value, "%Y-%m-%d")
            return True
        except ValueError:
            return False

    def to_number(self, value) -> float:

        value = value.replace(",", "").replace(" ", "")

        try:
            return float(value)
        except ValueError:
            return 0