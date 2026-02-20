import openpyxl

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
for name in wb.sheetnames:
    print(f"Name: '{name}' | Length: {len(name)}")
