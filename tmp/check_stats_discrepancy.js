// /tmp/check_stats_discrepancy.js
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
    
    // 1. Get stats summary
    const resStats = await client.query(`
      SELECT 
        COUNT(*) as total,
        trang_thai,
        COUNT(*) as count
      FROM ho_so_xet_tuyen
      GROUP BY trang_thai;
    `);
    console.log("Status Counts:");
    console.table(resStats.rows);

    // 2. Find records that are NOT in the standard dashboard counts
    const resOdd = await client.query(`
      SELECT so_cccd, ho_va_ten, trang_thai 
      FROM ho_so_xet_tuyen hs
      JOIN thi_sinh ts ON hs.so_cccd = ts.so_cccd
      WHERE hs.trang_thai NOT IN ('Chờ duyệt', 'Đã duyệt', 'Từ chối') 
         OR hs.trang_thai IS NULL;
    `);
    
    if (resOdd.rows.length > 0) {
      console.log("\nRecords with non-standard statuses:");
      console.table(resOdd.rows);
    } else {
      console.log("\nNo records with non-standard statuses found in ho_so_xet_tuyen.");
      
      // 3. Check for candidates without an application (if they are being counted in total)
      const resNoApp = await client.query(`
        SELECT ts.so_cccd, ts.ho_va_ten
        FROM thi_sinh ts
        LEFT JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
        WHERE hs.id IS NULL;
      `);
      console.log("\nCandidates without an application:");
      console.table(resNoApp.rows);
    }

  } catch (err) {
    console.error(err.message);
  } finally {
    await client.end();
  }
}
run();
