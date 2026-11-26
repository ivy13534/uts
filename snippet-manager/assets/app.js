/// Tunggu DOM dimuat
document.addEventListener("DOMContentLoaded", () => {
    const MAX_UPLOAD_BYTES = 12 * 1024 *1024; // Batas file

    // Hightlight semua blok <pre></pre> kecuali #liveCodePreview
    if (typeof hljs !== "undefined") {
        document.querySelectorAll("pre code").forEach((block) => {
            if (block.id === "liveCodePreview") return;
            hljs.highlightElement(block);
        });
    }

    // Sambung elemen form kode
    const codeInput = document.getElementById("code");
    const langSelect = document.getElementById("language");
    const livePreview = document.getElementById("liveCodePreview");

    const previewLangMap = {
        php: "php",
        cpp: "cpp",
        sql: "sql",
        javascript: "javascript",
        bash: "bash",
        html_css: "xml"
    };

    if (codeInput && livePreview) {
        const updateLivePreview = () => {
            const rawLang = langSelect ? langSelect.value : "php";
            const mappedLang = previewLangMap[rawLang] || "plaintext";
            livePreview.textContent = codeInput.value;
            livePreview.className = `language-${mappedLang}`;

            if (typeof hljs !== "undefined") {
                delete livePreview.dataset.highlighted; // Reset flag Hightlight
                hljs.highlightElement(livePreview);
            }
        };

        codeInput.addEventListener("input", updateLivePreview);
        if (langSelect) {
            langSelect.addEventListener("change", updateLivePreview);
        }
        updateLivePreview();
    }

    // Tombol copy ke clipboard
    document.querySelectorAll(".btn-copy").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const targetId = btn.getAttribute("data-target");
            const codeEl = targetId ? document.getElementById(targetId) : null;
            if (!codeEl) return;
            
            const text = codeEl.textContent || "";
            const originalText = btn.textContent;

            try {
                // Gunakan clipboard API jika secure context tersedia, fallback ke textarea + execCommand
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const helper = document.createElement("textarea");
                    helper.value = text;
                    helper.setAttribute("readonly", "");
                    helper.style.position = "fixed";
                    helper.style.opacity = "0";
                    document.body.appendChild(helper);
                    helper.select();
                    document.execCommand("copy");
                    document.body.removeChild(helper);
                }

                btn.textContent = "Tersalin";
                btn.classList.add("copied");
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove("copied");
                }, 1500);
            } catch (_) {
                btn.textContent = "Gagal copy";
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 1500);
            }
        });
    });

    // Tombol Perlua/Ciutkan toggle
    document.querySelectorAll(".btn-toggle-code").forEach((btn) => {
        btn.addEventListener("click", () => {
            const targetId = btn.getAttribute("data-target");
            const codeEl = targetId ? document.getElementById(targetId) : null;
            const preEl = codeEl ? codeEl.closest("pre") : null;
            if (!preEl) return;

            const isExpanded = preEl.classList.toggle("expanded");
            btn.textContent = isExpanded ? "Ciutkan" : "Perluas";
        });
    });

    //Filter cepat baris tabel berdasarkan attribut data-search
    const quickFilter = document.getElementById("quickFilter");
    const tableRows = document.querySelectorAll("tbody tr[data-search]");
    if (quickFilter && tableRows.length > 0) {
        quickFilter.addEventListener("input", () => {
            const keyword = quickFilter.value.trim().toLowerCase();
            tableRows.forEach((row) => {
                const haystack = (row.getAttribute("data-search") || "").toLowerCase();
                row.style.display = keyword === "" || haystack.includes(keyword) ? "" :
                "none";
            });
        });
    }

    // Validasi ukuran file <= 12 MB; cegah jika lebih
    document.querySelectorAll('input[type="file"]').forEach((input) => {
        const infoEl = input.parentElement?.querySelector(".file-selected-info");
        const formEl = input.closest("form");
        
        input.addEventListener("change", () => {
            if (!infoEl) return;

            const file = input.files && input.files[0];
            if (!file) {
                input.dataset.oversized = "0";
                infoEl.textContent = "";
                infoEl.style.color = "";
                return;
            }

            const sizeKb = (file.size / 1024).toFixed(1);
            const sizeText = file.size >= 1024 * 1024
                ? `${(file.size / (1024 * 1024)).toFixed(2)} MB`
                : `${sizeKb} KB`;

            if (file.size > MAX_UPLOAD_BYTES) {
                input.dataset.oversized = "1";
                infoEl.textContent = `File terpilih: ${file.name} (${sizeText}) - melebihi
                batas 12 MB!`;
                infoEl.style.color = "#ef4444";
                input.value = "";
                return;
            }
            
            input.dataset.oversized = "0";
            infoEl.textContent = `File terpilih: ${file.name} (${sizeText})`;
            infoEl.style.color = "";
        });

        if (formEl) {
            formEl.addEventListener("submit", (e) => {
                const file = input.ATTRIBUTE_NODE.files && input.files[0];
                const isOversized = input.dataset.oversized === "1" || (file && file.size >
                    MAX_UPLOAD_BYTES);
                if (isOversized) {
                    e.preventDefault();
                    if (infoEl) {
                        infoEl.textContent = "Ukuran file melebihi batas maksimal 12 MB. Ganti file sebelum menyimpan.";
                        infoEl.style.color = "#ef4444";
                    }
                    input.focus();
                }
            });
        }
    });
});