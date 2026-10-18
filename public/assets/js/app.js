/* Ridude Admin — interactions only.
 * All content, SVG icons, charts, rows and reusable templates live in HTML.
 * No HTML strings, page renderers or JavaScript data files are used.
 */
(function () {
    "use strict";

    function $(selector, root) {
        return (root || document).querySelector(selector);
    }

    function all(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function cloneTemplate(id) {
        var element = document.getElementById(id).content.firstElementChild;
        if (element.tagName.toLowerCase() === "svg") {
            element = element.firstElementChild;
        }
        return element.cloneNode(true);
    }

    function toast(message) {
        var element = $(".toast");
        element.textContent = message;
        element.classList.add("show");
        clearTimeout(element.timer);
        element.timer = setTimeout(function () {
            element.classList.remove("show");
        }, 2200);
    }

    function go(href) {
        document.body.classList.add("is-leaving");
        setTimeout(function () {
            window.location.href = href;
        }, 220);
    }

    function initShell() {
        var body = document.body;
        var mobile = window.matchMedia("(max-width: 768px)");
        try {
            if (localStorage.getItem("ridude-sidebar") === "collapsed")
                body.classList.add("sidebar-collapsed");
        } catch (error) {
            /* Storage is optional for local files. */
        }
        var toggle = $("#sidebar-toggle");
        if (toggle)
            toggle.addEventListener("click", function () {
                body.classList.toggle(
                    mobile.matches ? "mobile-open" : "sidebar-collapsed",
                );
                if (!mobile.matches) {
                    try {
                        localStorage.setItem(
                            "ridude-sidebar",
                            body.classList.contains("sidebar-collapsed")
                                ? "collapsed"
                                : "open",
                        );
                    } catch (error) {
                        /* Navigation works without storage. */
                    }
                }
            });
        var overlay = $(".overlay");
        if (overlay)
            overlay.addEventListener("click", function () {
                body.classList.remove("mobile-open");
            });
        var button = $("#profile-btn"),
            menu = $("#profile-menu");
        function closeMenu() {
            if (menu) menu.classList.remove("open");
            if (button) button.setAttribute("aria-expanded", "false");
        }
        if (button) {
            button.addEventListener("click", function (event) {
                event.stopPropagation();
                menu.classList.toggle("open");
                button.setAttribute(
                    "aria-expanded",
                    String(menu.classList.contains("open")),
                );
            });
            document.addEventListener("click", closeMenu);
        }
        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape") return;
            closeMenu();
            body.classList.remove("mobile-open");
            var modal = $("#modal");
            if (modal) modal.classList.remove("open");
        });
        var logout = $("#logout");
        if (logout)
            logout.addEventListener("click", function () {
                go("login.php");
            });
        var login = $("#login-form");
        if (login)
            login.addEventListener("submit", function (event) {
                event.preventDefault();
                go("index.php");
            });
        document.addEventListener("click", function (event) {
            var link = event.target.closest("a[href]");
            if (
                !link ||
                link.target ||
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey
            )
                return;
            var href = link.getAttribute("href");
            if (!href.endsWith(".php") || href.includes("://")) return;
            event.preventDefault();
            go(href);
        });
    }

    function initTabs(callback) {
        var tabs = $("#tabs");
        tabs.addEventListener("click", function (event) {
            var button = event.target.closest(".tab");
            if (!button) return;
            all(".tab", tabs).forEach(function (tab) {
                tab.classList.toggle("active", tab === button);
                tab.setAttribute("aria-pressed", String(tab === button));
            });
            callback(button.dataset.value);
        });
    }

    function filterRows(matches) {
        var rows = all("#rows > tr:not(.empty-row)");
        var visible = 0;
        rows.forEach(function (row) {
            row.hidden = !matches(row);
            if (!row.hidden) visible++;
        });
        var empty = $("#rows .empty-row");
        if (!empty) {
            empty = cloneTemplate("empty-template");
            empty.classList.add("empty-row");
            $("#rows").appendChild(empty);
        }
        empty.hidden = visible > 0;
        return visible;
    }

    function initRiders() {
        $("#q").addEventListener("input", function () {
            var query = this.value.toLowerCase();
            filterRows(function (row) {
                return Array.from(row.cells)
                    .slice(0, 3)
                    .some(function (cell) {
                        return cell.textContent.toLowerCase().includes(query);
                    });
            });
        });
    }

    function initDrivers() {
        var selected = "pending";
        function update() {
            var rows = all("#rows > tr:not(.empty-row)");
            all("[data-count]").forEach(function (counter) {
                counter.textContent =
                    counter.dataset.count === "total"
                        ? rows.length
                        : rows.filter(function (row) {
                              return (
                                  row.dataset.status === counter.dataset.count
                              );
                          }).length;
            });
            filterRows(function (row) {
                return selected === "all" || row.dataset.status === selected;
            });
        }
        initTabs(function (value) {
            selected = value;
            update();
        });
        $("#rows").addEventListener("click", function (event) {
            var button = event.target.closest("[data-act]");
            if (!button) return;
            var row = button.closest("tr");
            row.dataset.status = button.dataset.act;
            var badge = $(".badge", row);
            badge.textContent = row.dataset.status;
            badge.className =
                "badge " +
                (row.dataset.status === "approved" ? "tone-green" : "tone-red");
            all("[data-act]", row).forEach(function (action) {
                action.hidden = action.dataset.act === row.dataset.status;
            });
            toast("Driver " + row.dataset.status);
            update();
        });
        update();
    }

    function initWorkplaces() {
        var list = $("#list"),
            modal = $("#modal");
        function count(card) {
            $(".company-head .small", card).textContent =
                all(".branch", card).length + " branches";
        }
        function addBranch(card, address) {
            var branch = cloneTemplate("branch-template");
            $("span", branch).textContent = address;
            card.insertBefore(branch, $(".inline-form", card));
            count(card);
        }
        list.addEventListener("click", function (event) {
            var button = event.target.closest("[data-remove]");
            if (!button) return;
            var card = button.closest(".card");
            button.closest(".branch").remove();
            count(card);
        });
        list.addEventListener("submit", function (event) {
            event.preventDefault();
            var input = $("input", event.target),
                value = input.value.trim();
            if (!value) return;
            addBranch(event.target.closest(".card"), value);
            input.value = "";
            toast("Branch added");
        });
        function close() {
            modal.classList.remove("open");
            $("#add").focus();
        }
        $("#add").addEventListener("click", function () {
            modal.classList.add("open");
            $("#cname").focus();
        });
        $("#cancel").addEventListener("click", close);
        modal.addEventListener("click", function (event) {
            if (event.target === modal) close();
        });
        $("#save").addEventListener("click", function () {
            var name = $("#cname").value.trim(),
                address = $("#cbranch").value.trim();
            if (!name) {
                $("#cname").focus();
                return;
            }
            var card = cloneTemplate("company-template");
            all(".branch", card).forEach(function (branch) {
                branch.remove();
            });
            $("h2", card).textContent = name;
            card.dataset.id = "company-" + Date.now();
            card.classList.add("is-visible");
            if (address) addBranch(card, address);
            count(card);
            list.appendChild(card);
            $("#cname").value = "";
            $("#cbranch").value = "";
            close();
            toast("Company added");
        });
    }

    function initPayments() {
        $("#list").addEventListener("click", function (event) {
            var button = event.target.closest(".switch");
            if (!button) return;
            var active = button.classList.toggle("on");
            button.setAttribute("aria-checked", String(active));
            var badge = $(".badge", button.closest(".card"));
            badge.textContent = active ? "Active" : "Inactive";
            badge.className = "badge " + (active ? "tone-green" : "tone-red");
        });
        $("#form").addEventListener("submit", function (event) {
            event.preventDefault();
            var name = $("#mname").value.trim();
            if (!name) return;
            var card = cloneTemplate("method-template");
            $("h2", card).textContent = name;
            $("p", card).textContent = "Custom method";
            var button = $(".switch", card);
            button.classList.add("on");
            button.setAttribute("aria-label", "Toggle " + name);
            button.setAttribute("aria-checked", "true");
            button.dataset.id = "method-" + Date.now();
            $(".badge", card).textContent = "Active";
            $(".badge", card).className = "badge tone-green";
            card.classList.add("is-visible");
            $("#list").appendChild(card);
            $("#mname").value = "";
            toast("Payment method added");
        });
    }

    function initSubscriptions() {
        var plan = "all",
            driver = null;
        function update() {
            filterRows(function (row) {
                return (
                    (plan === "all" || row.dataset.plan === plan) &&
                    (!driver || row.dataset.driver === driver)
                );
            });
            all("#drivers [data-name]").forEach(function (button) {
                button.classList.toggle(
                    "selected",
                    button.dataset.name === driver,
                );
            });
        }
        initTabs(function (value) {
            plan = value;
            update();
        });
        $("#drivers").addEventListener("click", function (event) {
            var button = event.target.closest("[data-name]");
            if (!button) return;
            driver =
                driver === button.dataset.name ? null : button.dataset.name;
            update();
        });
    }

    function initTrips() {
        var period = "daily";
        var svg = $("#chart svg");
        var initialChart = svg.cloneNode(true);
        function label(date) {
            return date.toLocaleDateString("en-GB", {
                day: "2-digit",
                month: "short",
            });
        }
        function update() {
            var from = $("#from").value,
                to = $("#to").value;
            var count = filterRows(function (row) {
                return (
                    (!from || row.dataset.date >= from) &&
                    (!to || row.dataset.date <= to)
                );
            });
            $("#sub").textContent = count + " trips in selected range";
            var groups = new Map();
            all("#rows tr[data-date]")
                .reverse()
                .forEach(function (row) {
                    if (row.hidden) return;
                    var date = new Date(row.dataset.date + "T12:00:00"),
                        key;
                    if (period === "monthly")
                        key = date.toLocaleDateString("en-GB", {
                            month: "short",
                            year: "2-digit",
                        });
                    else if (period === "weekly") {
                        date.setDate(date.getDate() - date.getDay());
                        key = "Wk " + label(date);
                    } else key = label(date);
                    groups.set(key, (groups.get(key) || 0) + 1);
                });
            var values = Array.from(groups.entries());
            var max = Math.max(
                4,
                Math.ceil(
                    Math.max.apply(
                        null,
                        [1].concat(
                            values.map(function (pair) {
                                return pair[1];
                            }),
                        ),
                    ) / 4,
                ) * 4,
            );
            var next = initialChart.cloneNode(true);
            all(".bar", next).forEach(function (bar) {
                bar.remove();
            });
            all("text", next).forEach(function (text, index) {
                if (index < 5) text.textContent = Math.round((max * index) / 4);
                else text.remove();
            });
            var width = 586 / Math.max(1, values.length);
            values.forEach(function (pair, index) {
                var bar = cloneTemplate("bar-template");
                var height = (242 * pair[1]) / max;
                bar.setAttribute("x", 44 + index * width + width * 0.15);
                bar.setAttribute("y", 252 - height);
                bar.setAttribute("width", width * 0.7);
                bar.setAttribute("height", height);
                bar.dataset.tip = pair[0] + ": " + pair[1] + " trips";
                next.appendChild(bar);
                if (index % Math.ceil(values.length / 8) === 0) {
                    var text = cloneTemplate("chart-label-template");
                    text.setAttribute("x", 44 + index * width + width * 0.5);
                    text.setAttribute("y", 272);
                    text.setAttribute("text-anchor", "middle");
                    text.textContent = pair[0];
                    next.appendChild(text);
                }
            });
            svg.replaceWith(next);
            svg = next;
        }
        $("#from").addEventListener("change", update);
        $("#to").addEventListener("change", update);
        initTabs(function (value) {
            period = value;
            update();
        });
    }

    function initCharts() {
        var tip = $(".chart-tip");
        document.addEventListener("mousemove", function (event) {
            var target = event.target.closest("[data-tip]");
            tip.style.opacity = target ? "1" : "0";
            if (!target) return;
            tip.textContent = target.dataset.tip;
            tip.style.left =
                Math.min(
                    event.clientX + 12,
                    window.innerWidth - tip.offsetWidth - 8,
                ) + "px";
            tip.style.top = Math.max(8, event.clientY - 30) + "px";
        });
    }

    window.addEventListener("pageshow", function () {
        document.body.classList.remove("is-leaving");
    });
    document.addEventListener("DOMContentLoaded", function () {
        initShell();
        initCharts();
        var pages = {
            riders: initRiders,
            drivers: initDrivers,
            workplaces: initWorkplaces,
            payments: initPayments,
            subscriptions: initSubscriptions,
            trips: initTrips,
        };
        var init = pages[document.body.dataset.page];
        if (init) init();
        if ("IntersectionObserver" in window) {
            var observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) return;
                        entry.target.classList.add("is-visible");
                        observer.unobserve(entry.target);
                    });
                },
                { threshold: 0.08 },
            );
            all(".reveal").forEach(function (element) {
                observer.observe(element);
            });
        }
    });
})();
