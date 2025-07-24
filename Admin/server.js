const express = require("express");
const fs = require("fs");
const fsPromises = require("fs/promises");
const path = require("path");
const multer = require("multer");
const argon2 = require("argon2");
const { v4: uuidv4 } = require("uuid");

const app = express();
const PORT = 3000;

// Paths
const ADMIN_DIR = path.join(__dirname);
const JSON_DIR = path.join(__dirname, "../Json");
const IMAGE_DIR = path.join(__dirname, "../images");
const SESSION_FILE = path.join(__dirname, "data/sessions.json");
const USER_FILE = path.join(__dirname, "data/users.json");

// Serve static assets
app.use("/images", express.static(IMAGE_DIR));
app.use("/Admin", express.static(ADMIN_DIR));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Multer config for temporary uploads
const upload = multer({ dest: IMAGE_DIR });

// === ROUTES ===

// Admin Login Page
app.get("/Admin", (_, res) => {
    res.sendFile(path.join(ADMIN_DIR, "login.html"));
});

// Serve website static content (root)
app.use("/", express.static(path.join(__dirname, ".."))); // This serves index.html etc

app.get("*", (req, res) => {
    res.status(404).send("Page not found");
});
// Session Validation
app.post("/Admin/api/session", async (req, res) => {
    try {
        const { token } = req.body;
        const sessions = JSON.parse(await fsPromises.readFile(SESSION_FILE, "utf-8"));
        const session = sessions[token];
        if (!session || session.expires < Date.now()) return res.status(403).end();
        res.json({ username: session.username, role: session.role });
    } catch {
        res.status(500).end();
    }
});

// Login
app.post("/Admin/api/login", async (req, res) => {
    try {
        const { username, password } = req.body;
        const users = JSON.parse(await fsPromises.readFile(USER_FILE, "utf-8"));
        const user = users[username];
        if (!user || !(await argon2.verify(user.password, password))) return res.status(401).end();

        const token = uuidv4();
        const sessions = fs.existsSync(SESSION_FILE)
            ? JSON.parse(await fsPromises.readFile(SESSION_FILE, "utf-8"))
            : {};

        sessions[token] = {
            username,
            role: user.role,
            createdAt: Date.now(),
            ip: req.ip,
            userAgent: req.headers['user-agent'] || "",
            expires: Date.now() + 6 * 60 * 60 * 1000 // 6 hours
        };

        await fsPromises.writeFile(SESSION_FILE, JSON.stringify(sessions, null, 2));
        res.json({ token });
    } catch {
        res.status(500).end();
    }
});
// Session fetch data 
// app.get("/Admin/api/sessions", async (req, res) => {
//     try {
//         const sessions = JSON.parse(await fsPromises.readFile(SESSION_FILE, "utf-8"));
//         res.json(sessions);
//     } catch {
//         res.status(500).end();
//     }
// });

// GET JSON file content
app.get("/Admin/api/json", async (req, res) => {
    const { file } = req.query;
    const filePath = path.join(JSON_DIR, path.basename(file));
    if (!fs.existsSync(filePath)) return res.status(404).send("File not found");

    try {
        const data = await fsPromises.readFile(filePath, "utf-8");
        res.type("application/json").send(data);
    } catch {
        res.status(500).send("Error reading file");
    }
});

// POST JSON to save
app.post("/Admin/api/json", async (req, res) => {
    const { file, data } = req.body;
    if (!file || !data) return res.status(400).send("Missing file or data");

    const filePath = path.join(JSON_DIR, path.basename(file));
    try {
        await fsPromises.writeFile(filePath, JSON.stringify(data, null, 2), "utf-8");
        res.sendStatus(200);
    } catch (err) {
        console.error("Error writing JSON file:", err);
        res.status(500).send("Error writing file");
    }
});

// Image upload
app.post("/Admin/api/upload", upload.single("image"), async (req, res) => {
    try {
        const folder = req.body.folder || "";
        const ext = path.extname(req.file.originalname);
        const safeName = `${Date.now()}${ext}`;
        const subDir = path.join(IMAGE_DIR, folder);

        await fsPromises.mkdir(subDir, { recursive: true });

        const newPath = path.join(subDir, safeName);
        await fsPromises.rename(req.file.path, newPath);

        const relativePath = `images/${folder}/${safeName}`;
        res.json({ path: relativePath });
    } catch (err) {
        console.error("Upload failed:", err);
        res.status(500).json({ error: "Upload failed" });
    }
});

app.post("/Admin/api/track", express.text({ type: "*/*" }), async (req, res) => {
    const ANALYTICS_FILE = path.join(JSON_DIR, "analytics.json");

    try {
        const payload = JSON.parse(req.body);
        let data = [];
        if (fs.existsSync(ANALYTICS_FILE)) {
            data = JSON.parse(await fsPromises.readFile(ANALYTICS_FILE, "utf-8"));
        }
        data.push(payload);
        await fsPromises.writeFile(ANALYTICS_FILE, JSON.stringify(data, null, 2));
        res.sendStatus(200);
    } catch (err) {
        console.error("Tracking error:", err);
        res.status(500).send("Tracking failed.");
    }
});

// === START SERVER ===
app.listen(PORT, () => {
    console.log(`✅ Admin server running at http://localhost:${PORT}/Admin`);
});
