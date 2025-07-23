document.addEventListener("DOMContentLoaded", () => {
    const token = localStorage.getItem("session_token");
    if (!token) return window.location.href = "/Admin";

    const fetchSession = () =>
        fetch("/Admin/api/session", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ token })
        }).then(res =>
            res.status === 200 ? res.json() : Promise.reject()
        );

    fetchSession()
        .then(data => {
            document.getElementById("role-banner").textContent = `Logged in as ${data.username}`;
        })
        .catch(() => {
            localStorage.removeItem("session_token");
            window.location.href = "/Admin";
        });

    const fileSelect = document.getElementById("fileSelect");
    const addRowBtn = document.getElementById("addRowBtn");
    const cardContainer = document.getElementById("cardContainer");
    const modal = document.getElementById("modal");
    const modalForm = document.getElementById("modalForm");
    const modalTitle = document.getElementById("modalTitle");
    const logoutBtn = document.getElementById("logout-btn");
    const cmsSection = document.getElementById("cmsSection");
    const analyticsSection = document.getElementById("analyticsSection");
    const cmsBtn = document.getElementById("cmsBtn");
    const analyticsBtn = document.getElementById("analyticsBtn");

    const contentContainer = document.getElementById("contentCards");
    const addButton = document.getElementById("addRowBtn");
    const modalClose = document.getElementById("cancelModal");
    const confirmButton = document.getElementById("saveEntry");

    let currentData = [];
    let currentFile = '';
    let editIndex = null;

    modal.classList.add('hidden');

    logoutBtn.onclick = () => {
        localStorage.removeItem("session_token");
        window.location.href = "/Admin";
    };

    // Save the current section to localStorage
    function setCurrentSection(section) {
        localStorage.setItem('currentSection', section);
    }

    // Load the current section from localStorage
    function loadCurrentSection() {
        const section = localStorage.getItem('currentSection') || 'cmsSection';
        if (section === 'analyticsSection') {
            cmsSection.classList.add("hidden");
            analyticsSection.classList.remove("hidden");
            loadAnalytics();
        } else {
            analyticsSection.classList.add("hidden");
            cmsSection.classList.remove("hidden");
        }
    }

    // Update button click handlers to save the section
    cmsBtn.onclick = () => {
        setCurrentSection('cmsSection');
        cmsSection.classList.remove("hidden");
        analyticsSection.classList.add("hidden");
    };

    analyticsBtn.onclick = () => {
        setCurrentSection('analyticsSection');
        analyticsSection.classList.remove("hidden");
        cmsSection.classList.add("hidden");
        loadAnalytics();
    };

    // Load the correct section on page load
    document.addEventListener("DOMContentLoaded", () => {
        loadCurrentSection();
    });

    addRowBtn.style.display = "none";

    fileSelect.onchange = async () => {
        const file = fileSelect.value;
        if (!file) {
            contentContainer.innerHTML = "";
            addRowBtn.style.display = "none";
            return;
        }
        currentFile = file;
        const res = await fetch(`/Admin/api/json?file=${currentFile}`);
        currentData = await res.json();
        renderCards(currentData);
        addRowBtn.style.display = "inline-block";
    };

    const getImagePath = (src) => {
        if (!src) return "";
        return "/" + src.replace(/^\.?\/*/, '');
    };

    function renderCards(data) {
        contentContainer.innerHTML = '';

        data.forEach((entry, index) => {
            const card = document.createElement('div');
            card.className = 'bg-blue-900 shadow-md rounded-lg p-4 flex flex-col justify-between';

            let imagesHTML = '';
            if (entry.image) {
                if (Array.isArray(entry.image)) {
                    imagesHTML = entry.image.map(src => `<img src="${getImagePath(src)}" class="w-full h-40 object-cover mb-2 rounded">`).join('');
                } else {
                    imagesHTML = `<img src="${getImagePath(entry.image)}" class="w-full h-40 object-cover mb-2 rounded">`;
                }
            }

            let iconHTML = entry.icon && entry.icon.includes('<svg') ? `<div class="mb-2">${entry.icon}</div>` : '';

            let body = '';
            for (const [key, value] of Object.entries(entry)) {
                if (key === 'image' || key === 'icon') continue;

                if (typeof value === 'object' && !Array.isArray(value)) {
                    body += `<div><strong class="uppercase">${key}:</strong> ${Object.entries(value).map(([k, v]) => `${k}: ${v}`).join(', ')}</div>`;
                } else if (Array.isArray(value)) {
                    body += `<div><strong class="uppercase">${key}:</strong><ul class="list-disc pl-5 uppercase">${value.map(v => `<li>${v}</li>`).join('')}</ul></div>`;
                } else {
                    body += `<div><strong class="uppercase">${key}:</strong> ${value}</div>`;
                }
            }

            card.innerHTML = `
                ${iconHTML}
                ${imagesHTML}
                <div class="flex-1 space-y-2 text-sm">${body}</div>
                <div class="flex justify-end gap-4 mt-4">
                    <button class="text-blue-500" onclick="openModal(${index})">Edit</button>
                    <button class="text-red-500" onclick="deleteEntry(${index})">Delete</button>
                </div>
            `;

            contentContainer.appendChild(card);
        });
    }

    addButton.onclick = () => {
        openModal(null);
    };

    window.openModal = function (index) {
        modal.classList.remove('hidden');
        editIndex = index;
        modalTitle.textContent = index === null ? "Add New Entry" : "Edit Entry";
        modalForm.innerHTML = '';

        let ref = {};
        if (index === null) {
            if (currentData.length > 0) {
                ref = currentData[0]; // Use first item as schema reference
            }
        } else {
            ref = currentData[index];
        }

        for (let key in ref) {
            const value = ref[key];

            if (key === 'image') {
                modalForm.innerHTML += `
                    <label class="block mb-2 text-gray-200 font-semibold">${key}</label>
                    <input type="file" name="${key}" ${Array.isArray(value) ? 'multiple' : ''} accept="image/*"
                        class="mb-4 w-full rounded-lg border border-gray-700 bg-gray-800 text-gray-100 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-700 file:text-white hover:file:bg-blue-800 transition-colors duration-150" />
                `;
            } else if (key === 'features' || key === 'socials' || Array.isArray(value)) {
                modalForm.innerHTML += `
                    <label class="block mb-2 text-gray-200 font-semibold">${key}</label>
                    <textarea name="${key}" class="mb-4 w-full rounded-lg border border-gray-700 p-3 bg-gray-800 text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-600 transition-shadow duration-150" rows="4">${typeof value === 'object' ? JSON.stringify(value, null, 2) : value
                    }</textarea>
                `;
            } else {
                modalForm.innerHTML += `
                    <label class="block mb-2 text-gray-200 font-semibold">${key}</label>
                    <input type="text" name="${key}" value="${value || ''}"
                        class="mb-4 w-full rounded-lg border border-gray-700 p-3 bg-gray-800 text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-600 transition-shadow duration-150" />
                `;
            }
        }

        // If no reference fields (new file), allow dynamic addition
        if (Object.keys(ref).length === 0) {
            modalForm.innerHTML = `
                <div id="newFieldsContainer"></div>
                <button type="button" id="addFieldBtn" class="mb-4 bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition-colors duration-150">+ Add Field</button>
            `;
            document.getElementById('addFieldBtn').onclick = () => {
                const container = document.getElementById('newFieldsContainer');
                const idx = container.children.length;
                container.innerHTML += `
                    <div class="mb-4">
                        <input type="text" name="field_${idx}_name" placeholder="Field Name"
                            class="mb-2 w-full rounded-lg border border-gray-700 p-3 bg-gray-800 text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-600 transition-shadow duration-150" />
                        <input type="text" name="field_${idx}_value" placeholder="Field Value"
                            class="w-full rounded-lg border border-gray-700 p-3 bg-gray-800 text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-600 transition-shadow duration-150" />
                    </div>
                `;
            };
        }
    };

    modalClose.onclick = () => modal.classList.add('hidden');

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    confirmButton.onclick = async (e) => {
        e.preventDefault();

        const formData = new FormData(modalForm);
        const obj = {};
        const folder = currentFile.replace(".json", "");

        for (let [key, value] of formData.entries()) {
            if (key === '__newkey') {
                const [k, v] = value.split(':');
                if (k && v) obj[k.trim()] = v.trim();
                continue;
            }

            // Handle image file upload
            if (value instanceof File && value.name) {
                const imgForm = new FormData();
                imgForm.append('image', value);
                imgForm.append('folder', folder);

                try {
                    const res = await fetch(`/Admin/api/upload`, {
                        method: 'POST',
                        body: imgForm,
                    });
                    const uploaded = await res.json();
                    obj[key] = uploaded.path; // ✅ Save uploaded image path
                } catch (err) {
                    console.error("Image upload failed:", err);
                }
                continue;
            }

            // Handle JSON parsing (e.g., socials)
            if (typeof value === 'string' && value.trim().startsWith('{')) {
                try {
                    obj[key] = JSON.parse(value); // ✅ Parse as JSON object
                } catch {
                    console.warn(`Invalid JSON for key "${key}":`, value);
                    obj[key] = value; // Fallback to raw string
                }
                continue;
            }

            // Handle multiline text
            if (typeof value === 'string' && value.includes('\n')) {
                obj[key] = value.split('\n').map(v => v.trim()).filter(Boolean); // ✅ Handle multiline text as array
                continue;
            }

            // Save raw value or retain existing value
            obj[key] = value || currentData[editIndex]?.[key]; // Retain existing value if field is empty
        }

        // Update or add entry
        if (editIndex !== null && currentData[editIndex]) {
            currentData[editIndex] = { ...currentData[editIndex], ...obj }; // Merge with existing data
        } else {
            currentData.push(obj);
        }

        // Save to server
        await saveData(currentFile, currentData);

        modal.classList.add('hidden');
        renderCards(currentData);
    };

    // Dynamic confirmation modal
    const confirmModal = document.createElement('div');
    confirmModal.id = 'confirmModal';
    confirmModal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-60 z-50 hidden';
    confirmModal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full text-center relative">
            <button id="closeConfirmModal" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-2xl font-bold">&times;</button>
            <div id="confirmModalIcon" class="mb-4"></div>
            <h2 id="confirmModalTitle" class="text-xl font-bold mb-2 text-gray-900"></h2>
            <p id="confirmModalMessage" class="mb-6 text-gray-700"></p>
            <div class="flex justify-center gap-4">
                <button id="confirmModalYes" class="bg-blue-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors"></button>
                <button id="confirmModalNo" class="bg-gray-200 text-gray-800 px-5 py-2 rounded-lg font-semibold hover:bg-gray-300 transition-colors"></button>
            </div>
        </div>
    `;
    document.body.appendChild(confirmModal);

    let confirmAction = null;

    function showConfirmModal({
        title = "Are you sure?",
        message = "",
        icon = "",
        yesText = "Yes",
        noText = "Cancel",
        onConfirm = () => { },
        onCancel = () => { }
    }) {
        document.getElementById('confirmModalTitle').textContent = title;
        document.getElementById('confirmModalMessage').textContent = message;
        document.getElementById('confirmModalIcon').innerHTML = icon;
        document.getElementById('confirmModalYes').textContent = yesText;
        document.getElementById('confirmModalNo').textContent = noText;
        confirmModal.classList.remove('hidden');
        confirmAction = { onConfirm, onCancel };
    }

    document.getElementById('confirmModalYes').onclick = () => {
        if (confirmAction && typeof confirmAction.onConfirm === 'function') confirmAction.onConfirm();
        confirmModal.classList.add('hidden');
    };
    document.getElementById('confirmModalNo').onclick = () => {
        if (confirmAction && typeof confirmAction.onCancel === 'function') confirmAction.onCancel();
        confirmModal.classList.add('hidden');
    };
    document.getElementById('closeConfirmModal').onclick = () => {
        if (confirmAction && typeof confirmAction.onCancel === 'function') confirmAction.onCancel();
        confirmModal.classList.add('hidden');
    };
    confirmModal.addEventListener('click', (e) => {
        if (e.target === confirmModal) {
            if (confirmAction && typeof confirmAction.onCancel === 'function') confirmAction.onCancel();
            confirmModal.classList.add('hidden');
        }
    });

    // Use for delete
    window.deleteEntry = function (index) {
        showConfirmModal({
            title: "Delete Entry",
            message: "Are you sure you want to delete this entry? This action cannot be undone.",
            icon: `<svg class="mx-auto mb-2 w-10 h-10 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`,
            yesText: "Delete",
            noText: "Cancel",
            onConfirm: async () => {
                currentData.splice(index, 1);
                await saveData(currentFile, currentData);
                renderCards(currentData);
            }
        });
    };

    // Use for save confirmation (optional, call this before saving if you want confirmation)
    async function confirmAndSave(obj, isEdit) {
        return new Promise((resolve) => {
            showConfirmModal({
                title: isEdit ? "Update Entry" : "Add Entry",
                message: isEdit
                    ? "Are you sure you want to update this entry?"
                    : "Are you sure you want to add this new entry?",
                icon: `<svg class="mx-auto mb-2 w-10 h-10 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`,
                yesText: isEdit ? "Update" : "Add",
                noText: "Cancel",
                onConfirm: () => resolve(true),
                onCancel: () => resolve(false)
            });
        });
    }

    // Modify confirmButton.onclick to use the confirmation modal
    confirmButton.onclick = async (e) => {
        e.preventDefault();

        const formData = new FormData(modalForm);
        const obj = {};
        const folder = currentFile.replace(".json", "");

        for (let [key, value] of formData.entries()) {
            if (key === '__newkey') {
                const [k, v] = value.split(':');
                if (k && v) obj[k.trim()] = v.trim();
                continue;
            }

            // Handle image file upload
            if (value instanceof File && value.name) {
                const imgForm = new FormData();
                imgForm.append('image', value);
                imgForm.append('folder', folder);

                try {
                    const res = await fetch(`/Admin/api/upload`, {
                        method: 'POST',
                        body: imgForm,
                    });
                    const uploaded = await res.json();
                    obj[key] = uploaded.path; // ✅ Save uploaded image path
                } catch (err) {
                    console.error("Image upload failed:", err);
                }
                continue;
            }

            // Handle JSON parsing (e.g., socials)
            if (typeof value === 'string' && value.trim().startsWith('{')) {
                try {
                    obj[key] = JSON.parse(value); // ✅ Parse as JSON object
                } catch {
                    console.warn(`Invalid JSON for key "${key}":`, value);
                    obj[key] = value; // Fallback to raw string
                }
                continue;
            }

            // Handle multiline text
            if (typeof value === 'string' && value.includes('\n')) {
                obj[key] = value.split('\n').map(v => v.trim()).filter(Boolean); // ✅ Handle multiline text as array
                continue;
            }

            // Save raw value or retain existing value
            obj[key] = value || currentData[editIndex]?.[key]; // Retain existing value if field is empty
        }

        const confirmed = await confirmAndSave(obj, editIndex !== null);
        if (!confirmed) return;

        if (editIndex !== null) {
            currentData[editIndex] = obj;
        } else {
            currentData.push(obj);
        }

        await saveData(currentFile, currentData);
        modal.classList.add('hidden');
        renderCards(currentData);
    };

    async function saveData(file, data) {
        await fetch(`/Admin/api/json`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ file, data })
        });
    }

    async function loadAnalytics() {
        try {
            const res = await fetch('/Admin/api/json?file=analytics.json');
            const raw = await res.json();

            const today = new Date().toISOString().split('T')[0];
            const yesterday = new Date(Date.now() - 86400000).toISOString().split('T')[0];

            const dailyCounts = {};
            const hourlyCounts = Array(24).fill(0);
            const sources = {};
            const pages = {};
            let scrollTotal = 0, scrollCount = 0;

            raw.forEach(r => {
                const date = r.timestamp.split('T')[0];
                dailyCounts[date] = (dailyCounts[date] || 0) + 1;

                const hour = parseInt(r.timestamp.split('T')[1].split(':')[0]);
                hourlyCounts[hour]++;

                const src = r.utm_source || 'direct';
                sources[src] = (sources[src] || 0) + 1;

                const page = r.url;
                pages[page] = (pages[page] || 0) + 1;

                const sd = Object.entries(r.scroll_depth || {}).filter(([k, v]) => v).length * 25;
                if (sd > 0) {
                    scrollTotal += sd;
                    scrollCount++;
                }
            });

            const visitorsToday = dailyCounts[today] || 0;
            const visitorsYesterday = dailyCounts[yesterday] || 0;
            const diff = visitorsToday - visitorsYesterday;

            document.getElementById('visitorsToday').textContent = visitorsToday;
            document.getElementById('visitorsCompare').textContent = (diff >= 0 ? '+' : '') + diff;

            // Sparkline
            if (document.getElementById('sparklineChart')) {
                new frappe.Chart('#sparklineChart', {
                    type: 'line',
                    data: {
                        labels: Array.from({ length: 24 }, (_, i) => `${i}:00`),
                        datasets: [{ name: 'Visits', values: hourlyCounts }]
                    },
                    height: 300,
                    colors: ['#60a5fa'],
                    axisOptions: { xAxisMode: 'tick', yAxisMode: 'tick' },
                });
            }

            // Donut
            if (document.getElementById('trafficSourcesDonut')) {
                new frappe.Chart('#trafficSourcesDonut', {
                    type: 'donut',
                    data: {
                        labels: Object.keys(sources),
                        datasets: [{ values: Object.values(sources) }]
                    },
                    colors: ['#34d399', '#60a5fa', '#fbbf24', '#f87171'],
                });
            }

            // Bar chart
            const topPages = Object.entries(pages).sort((a, b) => b[1] - a[1]).slice(0, 5);
            if (document.getElementById('topPagesBar')) {
                new frappe.Chart('#topPagesBar', {
                    type: 'bar',
                    data: {
                        labels: topPages.map(e => e[0]),
                        datasets: [{ name: 'Pageviews', values: topPages.map(e => e[1]) }]
                    },
                    colors: ['#facc15']
                });
            }

            // Scroll Progress
            const avgScroll = scrollCount === 0 ? 0 : Math.round(scrollTotal / scrollCount);
            const scrollProgress = document.getElementById("scrollProgress");
            if (scrollProgress) {
                scrollProgress.style.width = avgScroll + "%";
                scrollProgress.textContent = avgScroll + "%";
            }
        } catch (error) {
            console.error("Error loading analytics data:", error);
        }
    }

    function createAnalyticsTabs() {
        const section = document.getElementById("analyticsSection");
        const tabContainer = document.createElement("div");
        tabContainer.className = "flex gap-4 mb-6 border-b border-gray-600 text-sm font-semibold";
        tabContainer.innerHTML = `
            <button class="tab-btn py-2 px-4 text-gray-300 border-b-2 border-transparent hover:text-white hover:border-blue-500" data-tab="traffic">🚦 Traffic</button>
            <button class="tab-btn py-2 px-4 text-gray-300 border-b-2 border-transparent hover:text-white hover:border-blue-500" data-tab="engagement">📈 Engagement</button>
            <button class="tab-btn py-2 px-4 text-gray-300 border-b-2 border-transparent hover:text-white hover:border-blue-500" data-tab="health">🛠 Performance</button>
        `;
        section.prepend(tabContainer);

        const allTabs = document.querySelectorAll(".analytics-tab");
        const tabBtns = document.querySelectorAll(".tab-btn");

        tabBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                const tab = btn.getAttribute("data-tab");
                tabBtns.forEach(b => b.classList.remove("border-blue-500", "text-white"));
                btn.classList.add("border-blue-500", "text-white");
                allTabs.forEach(el => el.classList.add("hidden"));
                document.getElementById(`tab-${tab}`).classList.remove("hidden");
            });
        });

        tabBtns[0].click(); // Default to first tab
    }

    function createDateFilterControls() {
        const section = document.getElementById("analyticsSection");
        const filterBox = document.createElement("div");
        filterBox.className = "mb-6 flex flex-wrap gap-3 items-center";
        filterBox.innerHTML = `
            <label class="text-gray-300">From:
                <input type="date" id="startDate" class="ml-2 p-1 bg-gray-700 text-white rounded border border-gray-600">
            </label>
            <label class="text-gray-300">To:
                <input type="date" id="endDate" class="ml-2 p-1 bg-gray-700 text-white rounded border border-gray-600">
            </label>
            <button id="filterAnalytics" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded">🔍 Filter</button>
            <button id="exportCSV" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded">📥 Export CSV</button>
        `;
        section.insertBefore(filterBox, section.children[1]);

        document.getElementById("filterAnalytics").onclick = () => {
            const from = document.getElementById("startDate").value;
            const to = document.getElementById("endDate").value;
            if (!from || !to) return alert("Please select both dates.");
            fetchFilteredAnalytics(from, to);
        };

        document.getElementById("exportCSV").onclick = () => exportAnalyticsCSV();
    }

    function exportAnalyticsCSV() {
        fetch("/Admin/api/json?file=analytics.json")
            .then(res => res.json())
            .then(data => {
                const headers = Object.keys(data[0]);
                const csvRows = [headers.join(",")];
                for (const row of data) {
                    const values = headers.map(h => JSON.stringify(row[h] ?? ""));
                    csvRows.push(values.join(","));
                }
                const blob = new Blob([csvRows.join("\n")], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `analytics-${Date.now()}.csv`;
                a.click();
                URL.revokeObjectURL(url);
            });
    }

    function fetchFilteredAnalytics(from, to) {
        fetch("/Admin/api/json?file=analytics.json")
            .then(res => res.json())
            .then(data => {
                const fromDate = new Date(from);
                const toDate = new Date(to);
                const filtered = data.filter(d => {
                    const date = new Date(d.timestamp);
                    return date >= fromDate && date <= toDate;
                });
                renderAnalytics(filtered);
            });
    }

    function renderAnalytics(data) {
        // reuse your chart update logic, e.g. drawTraffic(data), drawDonut(data), etc.
        // For now just update a number as a basic example
        const visitorsToday = data.filter(d => new Date(d.timestamp).toDateString() === new Date().toDateString()).length;
        document.getElementById("visitorsToday").textContent = visitorsToday;
    }

    // INIT
    if (document.getElementById("analyticsSection")) {
        createAnalyticsTabs();
        createDateFilterControls();
    }

    loadAnalytics();
    // Load when Analytics section is opened
    analyticsBtn.onclick = () => {
        cmsSection.classList.add("hidden");
        analyticsSection.classList.remove("hidden");
        loadAnalytics();
    };

});



