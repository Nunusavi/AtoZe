const express = require("express");
const fs = require("fs");
const fsPromises = require("fs/promises");
const path = require("path");
const multer = require("multer");
const argon2 = require("argon2");
const { v4: uuidv4 } = require("uuid");
const os = require("os");

const app = express();
const PORT = process.env.PORT || 3000;

const baseDir = path.join(__dirname, "..");
const ADMIN_DIR = path.join(__dirname);
const JSON_DIR = path.join(baseDir, "Json");
const IMAGE_DIR = path.join(baseDir, "images");
const SESSION_FILE = path.join(ADMIN_DIR, "data/sessions.json");
const USER_FILE = path.join(ADMIN_DIR, "data/users.json");

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve public assets
app.use("/", express.static(baseDir)); // serves index.html and all static pages
app.use("/Admin", express.static(ADMIN_DIR));
app.use("/images", express.static(IMAGE_DIR));

// Upload config
const upload = multer({ dest: IMAGE_DIR });

// ========== ROUTES ==========

// Admin login page
app.get("/Admin/", (req, res) => {
    res.sendFile(path.join(ADMIN_DIR, "login.html"));
});

// Session check
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
        let sessions = {};
        if (fs.existsSync(SESSION_FILE)) {
            sessions = JSON.parse(await fsPromises.readFile(SESSION_FILE, "utf-8"));
        }
        sessions[token] = {
            username,
            role: user.role,
            expires: Date.now() + 1000 * 60 * 60 * 6, // 6 hours
        };
        await fsPromises.writeFile(SESSION_FILE, JSON.stringify(sessions, null, 2));
        res.json({ token });
    } catch {
        res.status(500).end();
    }
});

// Get JSON data
app.get("/Admin/api/json", async (req, res) => {
    const { file } = req.query;
    if (!file) return res.status(400).send("Missing file parameter.");
    const filePath = path.join(JSON_DIR, path.basename(file));
    if (!fs.existsSync(filePath)) return res.status(404).send("File not found.");

    try {
        const content = await fsPromises.readFile(filePath, "utf-8");
        res.type("application/json").send(content);
    } catch {
        res.status(500).send("Failed to read file.");
    }
});

// Save JSON data
app.post("/Admin/api/json", async (req, res) => {
    const { file, data } = req.body;
    if (!file || !data) return res.status(400).send("Missing file or data.");

    const safeFile = path.basename(file);
    const filePath = path.join(JSON_DIR, safeFile);
    try {
        await fsPromises.writeFile(filePath, JSON.stringify(data, null, 2), "utf-8");
        res.sendStatus(200);
    } catch (err) {
        console.error("Error writing file:", err);
        res.status(500).send("Failed to write file.");
    }
});

// Upload image
app.post("/Admin/api/upload", upload.single("image"), async (req, res) => {
    try {
        const folder = req.body.folder || "";
        const ext = path.extname(req.file.originalname);
        const newFilename = `${Date.now()}${ext}`;
        const subDir = path.join(IMAGE_DIR, folder);
        await fsPromises.mkdir(subDir, { recursive: true });

        const newPath = path.join(subDir, newFilename);
        await fsPromises.rename(req.file.path, newPath);

        res.json({ path: `images/${folder}/${newFilename}` });
    } catch (err) {
        console.error("Upload failed:", err);
        res.status(500).json({ error: "Upload failed" });
    }
});

// Fallback 404
app.use((req, res) => {
    res.status(404).send("Page not found");
});

// Start server
app.listen(PORT, () => {
    const interfaces = os.networkInterfaces();
    const addresses = [];
    for (const name of Object.keys(interfaces)) {
        for (const iface of interfaces[name]) {
            if (iface.family === "IPv4" && !iface.internal) {
                addresses.push(iface.address);
            }
        }
    }
    console.log(`✅ Server running at:`);
    addresses.forEach(addr => {
        console.log(`   http://${addr}:${PORT}`);
    });
    console.log(`   http://localhost:${PORT}`);
});
