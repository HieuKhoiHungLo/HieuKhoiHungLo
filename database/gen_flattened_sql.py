import openpyxl

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
sheet = wb.active

sql_lines = [
    "TRUNCATE TABLE public.dm_xa CASCADE;",
    "TRUNCATE TABLE public.dm_huyen CASCADE;",
    "TRUNCATE TABLE public.dm_tinh CASCADE;"
]

provinces = {} # ma_tinh -> name
wards = []     # (ma_xa, ten_xa, ma_tinh)

for i, row in enumerate(sheet.iter_rows(min_row=1, values_only=True)):
    if isinstance(row[1], int):
        ma_tinh = str(row[2]).strip().zfill(2)
        ten_tinh = str(row[8]).strip()
        ma_xa = str(row[4]).strip().zfill(5)
        ten_xa = str(row[5]).strip().replace("'", "''")
        
        if ma_tinh not in provinces:
            provinces[ma_tinh] = ten_tinh.replace("'", "''")
        
        wards.append((ma_xa, ten_xa, ma_tinh))

for code, name in provinces.items():
    sql_lines.append(f"INSERT INTO public.dm_tinh (ma_tinh, ten_tinh) VALUES ('{code}', '{name}') ON CONFLICT (ma_tinh) DO UPDATE SET ten_tinh = EXCLUDED.ten_tinh;")

# Use a set to track unique ma_xa to avoid multiple inserts in the same batch which can still conflict in some DBs
seen_xa = set()
for ma_xa, ten_xa, ma_tinh in wards:
    if ma_xa not in seen_xa:
        sql_lines.append(f"INSERT INTO public.dm_xa (ma_xa, ten_xa, ma_tinh) VALUES ('{ma_xa}', '{ten_xa}', '{ma_tinh}') ON CONFLICT (ma_xa) DO UPDATE SET ten_xa = EXCLUDED.ten_xa, ma_tinh = EXCLUDED.ma_tinh;")
        seen_xa.add(ma_xa)

with open("d:/xampp/htdocs/TS/database/import_flattened_geo.sql", "w", encoding="utf-8") as f:
    f.write("\n".join(sql_lines))

print(f"Generated SQL with {len(provinces)} provinces and {len(wards)} wards.")
