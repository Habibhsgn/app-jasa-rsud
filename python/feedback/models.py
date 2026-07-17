from dataclasses import dataclass, field
from typing import List


@dataclass
class FeedbackRow:
    """
    Merepresentasikan satu baris data feedback BPJS.
    """

    no: int
    no_sep: str
    tgl_verifikasi: str
    biaya_riil_rs: float
    biaya_diajukan: float
    biaya_disetujui: float


@dataclass
class FeedbackSheet:
    """
    Menampung seluruh data untuk satu sheet.
    Contoh:
        RI
        RJ
    """

    name: str
    rows: List[FeedbackRow] = field(default_factory=list)

    def add(self, row: FeedbackRow):
        self.rows.append(row)

    @property
    def count(self) -> int:
        return len(self.rows)


@dataclass
class FeedbackResult:
    """
    Hasil akhir pembacaan seluruh PDF.
    """

    ri: FeedbackSheet = field(default_factory=lambda: FeedbackSheet("RI"))
    rj: FeedbackSheet = field(default_factory=lambda: FeedbackSheet("RJ"))

    @property
    def total(self) -> int:
        return self.ri.count + self.rj.count