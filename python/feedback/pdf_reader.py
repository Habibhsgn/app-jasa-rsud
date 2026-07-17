import re
from datetime import datetime

import pdfplumber

from models import (
    FeedbackResult,
    FeedbackRow,
)


class PdfReader:

    def read(self, pdf_path: str) -> FeedbackResult:
        """
        Membaca satu file PDF feedback BPJS.

        Return:
            FeedbackResult
        """

        result = FeedbackResult()

        with pdfplumber.open(pdf_path) as pdf:

            pelayanan = self.detect_service(pdf)

            if pelayanan is None:
                raise Exception(
                    f"Tingkat Pelayanan tidak ditemukan pada file : {pdf_path}"
                )

            for page in pdf.pages:

                tables = page.extract_tables()

                if not tables:
                    continue

                for table in tables:

                    self.read_table(
                        table,
                        pelayanan,
                        result
                    )

        return result

    def detect_service(self, pdf) -> str | None:
        """
        Mendeteksi apakah file RITL atau RJTL.
        """

        for page in pdf.pages[:2]:

            text = page.extract_text() or ""

            if "RITL" in text:
                return "RI"

            if "RJTL" in text:
                return "RJ"

        return None

    def read_table(
        self,
        table,
        pelayanan,
        result: FeedbackResult
    ):

        for row in table:

            feedback = self.parse_row(row)

            if feedback is None:
                continue

            if pelayanan == "RI":
                result.ri.add(feedback)
            else:
                result.rj.add(feedback)

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

    def clean(
        self,
        value
    ) -> str:

        if value is None:
            return ""

        return (
            str(value)
            .replace("\n", " ")
            .strip()
        )

    def is_number(
        self,
        value
    ) -> bool:

        return value.isdigit()

    def is_sep(
        self,
        value
    ) -> bool:

        return bool(
            re.match(
                r'^\d+[A-Z]\d+',
                value
            )
        )

    def is_date(
        self,
        value
    ) -> bool:

        try:

            datetime.strptime(
                value,
                "%Y-%m-%d"
            )

            return True

        except:

            return False

    def to_number(
        self,
        value
    ) -> float:

        value = (
            value
            .replace(",", "")
            .replace(" ", "")
        )

        try:
            return float(value)
        except:
            return 0