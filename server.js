// Local / self-hosted launcher. On Vercel, api/index.js is the entry instead.
import app from './src/app.js';
import { initDb } from './src/db.js';

const PORT = process.env.PORT || 8000;

initDb().then(() => {
  app.listen(PORT, () => console.log(`\n  📖 ReadIn52 running at http://localhost:${PORT}\n`));
}).catch((err) => {
  console.error('Failed to start ReadIn52:', err);
  process.exit(1);
});
