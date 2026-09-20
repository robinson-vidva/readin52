import express from 'express';
import session from 'express-session';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { initDb } from './db.js';
import { makeSessionStore } from './sessionStore.js';
import { attachUser } from './auth.js';
import { appConfig } from './services/readingPlan.js';
import pagesRouter from './routes/pages.js';
import apiRouter from './routes/api.js';
import adminRouter from './routes/admin.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.join(__dirname, '..');
const ASSET_VER = (process.env.VERCEL_GIT_COMMIT_SHA || Date.now().toString(36)).slice(0, 12);

const app = express();
app.set('view engine', 'ejs');
app.set('views', path.join(ROOT, 'views'));
app.set('trust proxy', 1);
app.disable('x-powered-by');

// Ensure the database is initialized (schema + seed + caches) before any request.
// Cached promise, so this only does real work on the first request per instance.
app.use((req, res, next) => { initDb().then(() => next()).catch(next); });

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use('/static', express.static(path.join(ROOT, 'public'), { maxAge: '7d' }));
app.get('/manifest.webmanifest', (req, res) => res.sendFile(path.join(ROOT, 'public', 'manifest.webmanifest')));
app.get('/sw.js', (req, res) => { res.type('application/javascript'); res.sendFile(path.join(ROOT, 'public', 'js', 'sw.js')); });

app.use(session({
  store: makeSessionStore(session),
  secret: process.env.SESSION_SECRET || 'readin52-dev-secret-change-me',
  resave: false,
  saveUninitialized: false,
  cookie: {
    httpOnly: true,
    sameSite: 'lax',
    secure: process.env.NODE_ENV === 'production',
    maxAge: 1000 * 60 * 60 * 24 * 30,
  },
}));

app.use((req, res, next) => {
  res.locals.flash = req.session.flash || null;
  delete req.session.flash;
  req.flash = (type, message) => { req.session.flash = { type, message }; };
  res.locals.app = appConfig();
  res.locals.path = req.path;
  res.locals.query = req.query;
  res.locals.pageScripts = [];
  res.locals.assetVer = ASSET_VER;
  next();
});

app.use(attachUser);

app.use('/', pagesRouter);
app.use('/api', apiRouter);
app.use('/admin', adminRouter);

app.use((req, res) => {
  res.status(404).render('error', { title: 'Page not found', message: 'The page you are looking for does not exist.' });
});

app.use((err, req, res, next) => {
  console.error('ReadIn52 error:', err);
  res.status(500).render('error', { title: 'Something went wrong', message: 'We encountered an error processing your request.' });
});

export default app;
