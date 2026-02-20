import openpyxl

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
sheet = wb.active

# Specifically look at rows 5 to 8 for headers
for i in range(5, 9):
    row = [cell.value for cell in sheet[i]]
    print(f"Row {i}: {row}")
