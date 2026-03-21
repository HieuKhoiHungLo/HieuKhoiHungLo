// /tmp/check_stats_discrepancy_v4.js
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
    
    const out = {};

    // 1. Status Counts
    const resStats = await client.query(`
      SELECT trang_thai, COUNT(*) as count
      FROM ho_so_xet_tuyen
      GROUP BY trang_thai;
    `);
    out.status_counts = resStats.rows;

    // 2. Unusual statuses
    const resOdd = await client.query(`
      SELECT hs.so_cccd, ts.ho_va_ten, hs.trang_thai
      FROM ho_so_xet_tuyen hs
      JOIN thi_sinh ts ON hs.so_cccd = ts.so_cccd
      WHERE hs.trang_thai NOT IN ('Chờ duyệt', 'Đã duyệt', 'Từ chối') 
         OR hs.trang_thai IS NULL;
    `);
    out.unusual_records = resOdd.rows;

    // 3. Totals
    const resTotalTS = await client.query("SELECT COUNT(*) as ts_count FROM thi_sinh");
    const resTotalHS = await client.query("SELECT COUNT(*) as hs_count FROM ho_so_xet_tuyen");
    out.totals = {
        thi_sinh: parseInt(resTotalTS.rows[0].ts_count),
        ho_so_xet_tuyen: parseInt(resTotalHS.rows[0].hs_count)
    };

    // 4. No application
    const resNoApp = await client.query(`
      SELECT ts.so_cccd, ts.ho_va_ten
      FROM thi_sinh ts
      LEFT JOIN ho_so_xet_tuyen hs ON ts.so_cccd = hs.so_cccd
      WHERE hs.id IS NULL;
    `);
    out.candidates_without_application = resNoApp.rows;

    console.log(JSON.stringify(out, null, 2));

  } catch (err) {
    console.error("Error:", err.message);
  } finally {
    await client.end();
  }
}
run();
