import { Call, Events } from "/wails/runtime.js";

const service = "main.SettingsService.";
const call = (method, ...args) => Call.ByName(service + method, ...args);

const fields = [
    "address",
    "port",
    "sessionName",
    "editor",
    "enableEncryption",
    "symmetricKey",
    "enableSignVerification",
    "signPrivateKey",
    "tlsCert",
    "tlsPrivateKey",
];
const $ = (id) => document.getElementById(id);
const form = $("form");
const message = $("message");
let saved = null;

function fill(settings) {
    for (const name of fields) {
        const el = $(name);
        if (el.type === "checkbox") {
            el.checked = !!settings[name];
        } else {
            el.value = settings[name] ?? "";
        }
    }
    syncDependents();
}

function read() {
    const settings = {};
    for (const name of fields) {
        const el = $(name);
        if (el.type === "checkbox") {
            settings[name] = el.checked;
        } else if (el.type === "number") {
            settings[name] = parseInt(el.value, 10) || 0;
        } else {
            settings[name] = el.value.trim();
        }
    }
    return settings;
}

function syncDependents() {
    for (const row of document.querySelectorAll("[data-depends]")) {
        row.classList.toggle("hidden", !$(row.dataset.depends).checked);
    }
}

function say(text, ok = false) {
    message.textContent = text;
    message.classList.toggle("ok", ok);
}

async function refreshStatus() {
    const status = await call("Status");
    const el = $("status");
    el.classList.toggle("running", status.running);
    el.textContent = status.running
        ? (status.url || "Starting…")
        : "Stopped";
    $("version").textContent = status.version || " ";
    $("meta").textContent = `CLI: ${status.binary} · Settings: ${status.configDir}`;
}

async function refreshKeys() {
    const keys = await call("Keys");
    $("symmetricKeyValue").value = keys.symmetricKey || "";
    $("signKeyValue").value = keys.signPrivateKey || "";
    $("symmetricKeyBlock").classList.toggle("hidden", !keys.symmetricKey);
    $("signKeyBlock").classList.toggle("hidden", !keys.signPrivateKey);
    $("keys").classList.toggle("hidden", !keys.symmetricKey && !keys.signPrivateKey);
}

async function load() {
    const [settings, editors] = await Promise.all([call("Get"), call("Editors")]);
    $("editor").replaceChildren(...editors.map((name) => new Option(name, name)));
    saved = settings;
    fill(settings);
    await Promise.all([refreshStatus(), refreshKeys()]);
}

form.addEventListener("change", syncDependents);

form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const settings = read();
    $("save").disabled = true;
    say("");
    try {
        await call("Save", settings);
        saved = settings;
        say("Saved, server restarted.", true);
        await refreshKeys();
    } catch (err) {
        say(err.message || String(err));
    } finally {
        $("save").disabled = false;
        refreshStatus();
    }
});

$("revert").addEventListener("click", () => {
    fill(saved);
    say("");
});

$("defaults").addEventListener("click", async () => {
    fill(await call("Defaults"));
    say("Defaults loaded, save to apply.", true);
});

$("regenerate").addEventListener("click", async () => {
    try {
        await call("RegenerateKeys");
        await refreshKeys();
        say("Keys regenerated, clients need the new keys.", true);
    } catch (err) {
        say(err.message || String(err));
    }
});

for (const button of document.querySelectorAll("[data-pick]")) {
    button.addEventListener("click", async () => {
        const title = button.closest(".row").querySelector("label").firstChild.textContent.trim();
        const path = await call("PickFile", "Choose " + title);
        if (path) {
            $(button.dataset.pick).value = path;
        }
    });
}

for (const button of document.querySelectorAll("[data-copy]")) {
    button.addEventListener("click", async () => {
        await call("Copy", $(button.dataset.copy).value);
        const label = button.textContent;
        button.textContent = "Copied";
        setTimeout(() => (button.textContent = label), 1200);
    });
}

$("showLog").addEventListener("click", () => call("ShowLog"));

Events.On("server:status", refreshStatus);

load().catch((err) => say(err.message || String(err)));
