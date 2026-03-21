
const { createClient } = require('@supabase/supabase-js');
require('dotenv').config();

const supabase = createClient(process.env.SUPABASE_URL, process.env.SUPABASE_KEY);

async function checkCandidates() {
    const { data: totalCandidates, count: totalCount } = await supabase
        .from('thi_sinh')
        .select('*', { count: 'exact' });

    const { data: apps, count: appCount } = await supabase
        .from('ho_so_xet_tuyen')
        .select('so_cccd', { count: 'exact' });

    const uniqueAppCccds = new Set(apps.map(a => a.so_cccd));

    const total = totalCount;
    const withApp = uniqueAppCccds.size;
    const withoutApp = total - withApp;

    console.log(`Total candidates in thi_sinh: ${total}`);
    console.log(`Candidates with at least one application: ${withApp}`);
    console.log(`Candidates with NO applications: ${withoutApp}`);

    // Check sessions
    const { data: sessions } = await supabase.from('dot_tuyen_sinh').select('id, ten_dot, nam_tuyen_sinh');
    console.log('\nApplications per session:');
    for (const s of sessions) {
        const { count } = await supabase
            .from('ho_so_xet_tuyen')
            .select('*', { count: 'exact', head: true })
            .eq('dot_tuyen_sinh_id', s.id);
        console.log(`- ${s.ten_dot} (${s.nam_tuyen_sinh}, ID: ${s.id}): ${count}`);
    }
}

checkCandidates();
