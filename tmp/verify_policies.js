// /tmp/verify_policies.js
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
    const res = await client.query(`
      SELECT tablename, policyname 
      FROM pg_policies 
      WHERE policyname = 'Private: Service role only';
    `);
    console.log(JSON.stringify(res.rows, null, 2));
  } catch (err) {
    console.error(err.message);
  } finally {
    await client.end();
  }
}
run();
