// /tmp/check_stats_discrepancy_v2.js
const { Client } = require('pg');
const config = {
  host: 'aws-1-ap-south-1.pooler.supabase.com',
  port: 6543,
  database: 'postgres',
  user: 'postgres.oxhuzfqvlpntlymdwfiy',
  password: 'HvuTuyenSinh2026',
  ssl: { rejectUnauthorized: false }
};

async function run() {
  const client = new Client(config);
  try {
    await client.connect();
    
    console.log("--- 1. Status Counts in ho_so_xet_tuyen ---");
    const resStats = await client.query(`
      SELECT 
        trang_thai,
        COUNT(*) as count
      FROM ho_so_xet_tuyen
      GROUP BY trang_thai;
    `);
    console.table(resStats.rows);

    console.log("\n--- 2. Records with unusual statuses ---");
    const resOdd = await client.query(`
      SELECT hs.so_cccd, ts.ho_va_ten, hs.trang_thai, hs.id as application_id
      FROM ho_so_xet_tuyen hs
      JOIN thi_sinh ts ON hs.so_cccd = ts.so_cccd
      WHERE hs.trang_thai NOT IN ('Chờ duyệt', 'Đã duyệt', 'Từ chối') 
         OR hs.trang_thai IS NULL;
    `);
    console.table(resOdd.rows);

    console.log("\n--- 3. Total in thi_sinh table ---");
    const resTotalTS = await client.query("SELECT COUNT(*) FROM thi_sinh");
    console.log("Total thi_sinh:", resTotalTS.rows[0].count);

    console.log("\n--- 4. Candidates WITHOUT any application ---");
    const resNoApp = await client.query(`
      SELECT ts.so_cccd, ts.ho_va_ten, ts.created_at
      FROM thi_sinh ts
      LEFT JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
      WHERE hs.id IS NULL;
    `);
    console.table(resNoApp.rows);

  } catch (err) {
    console.error("Error:", err.message);
  } finally {
    await client.end();
  }
}
run();
