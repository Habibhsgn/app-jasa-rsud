import sys
import traceback

from pdf_reader import PdfReader
from excel_writer import ExcelWriter
from models import FeedbackResult


def merge_result(destination: FeedbackResult, source: FeedbackResult):
    """
    Menggabungkan hasil pembacaan beberapa PDF.
    """

    destination.ri.rows.extend(source.ri.rows)
    destination.rj.rows.extend(source.rj.rows)


def main():

    #
    # Format:
    #
    # python extract_feedback.py
    #     file1.pdf
    #     file2.pdf
    #     output.xlsx
    #
    #

    if len(sys.argv) < 3:
        raise Exception(
            "Parameter tidak lengkap."
        )

    pdf_files = sys.argv[1:-1]

    output_file = sys.argv[-1]

    reader = PdfReader()

    result = FeedbackResult()

    for pdf in pdf_files:

        current = reader.read(pdf)

        merge_result(
            result,
            current
        )

    writer = ExcelWriter()

    writer.write(
        result,
        output_file
    )

    print(output_file)


if __name__ == "__main__":

    try:

        main()

        sys.exit(0)

    except Exception as e:

        print(str(e))

        traceback.print_exc()

        sys.exit(1)