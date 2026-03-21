// /tmp/read_output.js
const fs = require('fs');
try {
    const data = fs.readFileSync('output.txt', 'utf16le');
    console.log(data);
} catch (e) {
    console.error(e.message);
}
