import { Call, Events } from "/wails/runtime.js";

const call = (method, ...args) => Call.ByName("main.SettingsService." + method, ...args);
const $ = (id) => document.getElementById(id);
const log = $("log");

async function refreshLog() {
    log.textContent = await call("Logs");
    if ($("follow").checked) {
        log.scrollTop = log.scrollHeight;
    }
}

async function refreshStatus() {
    const status = await call("Status");
    const el = $("status");
    el.classList.toggle("running", status.running);
    el.textContent = status.running ? (status.url || "Starting…") : "Stopped";
}

$("copy").addEventListener("click", async () => {
    await call("Copy", log.textContent);
    $("copy").textContent = "Copied";
    setTimeout(() => ($("copy").textContent = "Copy"), 1200);
});

$("clear").addEventListener("click", async () => {
    await call("ClearLogs");
    refreshLog();
});

$("follow").addEventListener("change", refreshLog);

let timer;
Events.On("server:log", () => {
    clearTimeout(timer);
    timer = setTimeout(refreshLog, 100);
});
Events.On("server:status", () => {
    refreshStatus();
    refreshLog();
});

refreshStatus();
refreshLog();
