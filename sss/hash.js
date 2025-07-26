// hash.js
const argon2 = require('argon2');

(async () => {
  const hashadmin = await argon2.hash('Atoze2025!?admin');
  const hashviewer = await argon2.hash('Atoze2025!?viewer');
  console.log(hashviewer);
  console.log(hashadmin);
})();