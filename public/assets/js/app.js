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

        var input = $("#q");
        var container = $("#rider-list-area");

        if (!input || !container) {
            return;
        }

        var timer = null;


        function loadRiders(
            url,
            updateHistory
        ) {

            if (updateHistory === undefined) {
                updateHistory = true;
            }

            container.classList.add(
                "is-loading"
            );


            fetch(url, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "text/html"
                }
            })
            .then(function (response) {

                if (!response.ok) {
                    throw new Error(
                        "Unable to load riders."
                    );
                }

                return response.text();

            })
            .then(function (html) {

                container.innerHTML = html;

                if (updateHistory) {

                    window.history.pushState(
                        {},
                        "",
                        url
                    );
                }

            })
            .catch(function (error) {

                console.error(error);

                toast(
                    "Unable to load riders."
                );

            })
            .finally(function () {

                container.classList.remove(
                    "is-loading"
                );

            });
        }


        /*
        * Search
        */
        input.addEventListener(
            "input",
            function () {

                clearTimeout(timer);

                timer = setTimeout(
                    function () {

                        var url = new URL(
                            window.location.href
                        );

                        var keyword =
                            input.value.trim();

                        if (keyword) {

                            url.searchParams.set(
                                "q",
                                keyword
                            );

                        } else {

                            url.searchParams.delete(
                                "q"
                            );
                        }

                        url.searchParams.delete(
                            "page"
                        );

                        loadRiders(
                            url.toString(),
                            true
                        );

                    },
                    500
                );

            }
        );


        /*
        * Pagination
        */
        container.addEventListener(
            "click",
            function (event) {

                var link =
                    event.target.closest(
                        ".pagination a"
                    );

                if (!link) {
                    return;
                }

                event.preventDefault();

                loadRiders(
                    link.href,
                    true
                );

            }
        );


        /*
        * Back/forward
        */
        window.addEventListener(
            "popstate",
            function () {

                var url =
                    window.location.href;

                var params =
                    new URL(url)
                        .searchParams;

                input.value =
                    params.get("q") || "";

                loadRiders(
                    url,
                    false
                );

            }
        );

    }

    function initDrivers() {

        var container =
            $("#driver-list-area");

        var input =
            $("#driver-q");

        var statusInput =
            $("#driver-status");

        var tabs =
            $("#driver-tabs");

        if (
            !container ||
            !tabs
        ) {
            return;
        }

        var timer = null;


        function loadDrivers(
            url,
            updateHistory
        ) {

            if (
                updateHistory === undefined
            ) {
                updateHistory = true;
            }


            container.classList.add(
                "is-loading"
            );


            fetch(url, {

                headers: {
                    "X-Requested-With":
                        "XMLHttpRequest",

                    "Accept":
                        "text/html"
                }

            })
            .then(function (response) {

                if (!response.ok) {
                    throw new Error(
                        "Unable to load drivers."
                    );
                }

                return response.text();

            })
            .then(function (html) {

                container.innerHTML = html;


                if (updateHistory) {

                    window.history.pushState(
                        {},
                        "",
                        url
                    );
                }

            })
            .catch(function (error) {

                console.error(error);

                toast(
                    "Unable to load drivers."
                );

            })
            .finally(function () {

                container.classList.remove(
                    "is-loading"
                );

            });
        }


        /*
        |--------------------------------------------------------------------------
        | Tabs
        |--------------------------------------------------------------------------
        */

        tabs.addEventListener(
            "click",
            function (event) {

                var button =
                    event.target.closest(
                        "[data-status]"
                    );

                if (!button) {
                    return;
                }


                var status =
                    button.dataset.status;


                all(
                    "[data-status]",
                    tabs
                ).forEach(
                    function (tab) {

                        tab.classList.toggle(
                            "active",
                            tab === button
                        );

                    }
                );


                statusInput.value =
                    status;


                var url =
                    new URL(
                        window.location.href
                    );


                url.searchParams.set(
                    "status",
                    status
                );


                url.searchParams.delete(
                    "page"
                );


                loadDrivers(
                    url.toString(),
                    true
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (input) {

            input.addEventListener(
                "input",
                function () {

                    clearTimeout(timer);


                    timer = setTimeout(
                        function () {

                            var url =
                                new URL(
                                    window.location.href
                                );


                            var keyword =
                                input.value.trim();


                            if (keyword) {

                                url.searchParams.set(
                                    "q",
                                    keyword
                                );

                            } else {

                                url.searchParams.delete(
                                    "q"
                                );
                            }


                            url.searchParams.delete(
                                "page"
                            );


                            loadDrivers(
                                url.toString(),
                                true
                            );

                        },
                        500
                    );

                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        container.addEventListener(
            "click",
            function (event) {

                var link =
                    event.target.closest(
                        ".ridude-pagination a"
                    );

                if (!link) {
                    return;
                }


                event.preventDefault();


                loadDrivers(
                    link.href,
                    true
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Approve / Reject
        |--------------------------------------------------------------------------
        */

        container.addEventListener(
            "click",
            function (event) {

                var button =
                    event.target.closest(
                        ".driver-status-btn"
                    );

                if (!button) {
                    return;
                }


                var driverId =
                    button.dataset.id;

                var status =
                    button.dataset.status;


                var message =
                    status === "approved"
                        ? "Approve this driver?"
                        : "Reject this driver?";


                if (!confirm(message)) {
                    return;
                }


                button.disabled = true;


                fetch(
                    "/admin/drivers/" +
                    driverId +
                    "/status",
                    {

                        method: "POST",

                        headers: {

                            "Accept":
                                "application/json",

                            "Content-Type":
                                "application/json",

                            "X-CSRF-TOKEN":
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    .getAttribute(
                                        "content"
                                    ),

                            "X-Requested-With":
                                "XMLHttpRequest"
                        },

                        body:
                            JSON.stringify({
                                status: status
                            })
                    }
                )
                .then(function (response) {

                    return response.json();

                })
                .then(function (data) {

                    if (!data.status) {

                        throw new Error(
                            data.message ||
                            "Unable to update driver."
                        );
                    }


                    toast(
                        data.message
                    );


                    loadDrivers(
                        window.location.href,
                        false
                    );

                })
                .catch(function (error) {

                    console.error(error);

                    toast(
                        error.message
                    );

                    button.disabled =
                        false;

                });

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Back / Forward
        |--------------------------------------------------------------------------
        */

        window.addEventListener(
            "popstate",
            function () {

                var url =
                    new URL(
                        window.location.href
                    );


                var status =
                    url.searchParams.get(
                        "status"
                    ) || "pending";


                if (statusInput) {

                    statusInput.value =
                        status;
                }


                if (input) {

                    input.value =
                        url.searchParams.get(
                            "q"
                        ) || "";
                }


                all(
                    "[data-status]",
                    tabs
                ).forEach(
                    function (tab) {

                        tab.classList.toggle(
                            "active",
                            tab.dataset.status ===
                                status
                        );

                    }
                );


                loadDrivers(
                    url.toString(),
                    false
                );

            }
        );
    }

    function initWorkplaces() {

        var modal =
            document.getElementById("workplace-modal");

        var addButton =
            document.getElementById("add-workplace-btn");

        var cancelButton =
            document.getElementById("workplace-cancel");

        var saveButton =
            document.getElementById("workplace-save");

        var list =
            document.getElementById("workplace-list");


        if (!modal) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Open modal
        |--------------------------------------------------------------------------
        */

        if (addButton) {

            addButton.addEventListener(
                "click",
                function () {

                    modal.classList.add("open");
                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Close modal
        |--------------------------------------------------------------------------
        */

        if (cancelButton) {

            cancelButton.addEventListener(
                "click",
                function () {

                    modal.classList.remove("open");

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Close when clicking backdrop
        |--------------------------------------------------------------------------
        */

        modal.addEventListener(
            "click",
            function (event) {

                if (event.target === modal) {

                    modal.classList.remove("open");

                }

            }
        );

        /*
        |--------------------------------------------------------------------------
        | Save multiple branch
        |--------------------------------------------------------------------------
        */

        list.addEventListener(
        "click",
        function (event) {

            /*
            |--------------------------------------------------------------------------
            | Add another branch input
            |--------------------------------------------------------------------------
            */

            var addMoreButton =
                event.target.closest(
                    ".add-more-inline-branch"
                );

            if (addMoreButton) {

                var workplaceId =
                    addMoreButton.dataset.id;

                var wrapper =
                    document.querySelector(
                        '.inline-branch-inputs[data-id="' +
                        workplaceId +
                        '"]'
                    );

                if (!wrapper) {
                    return;
                }


                var row =
                    document.createElement("div");

                row.className =
                    "inline-branch-row";


                row.innerHTML = `
                    <input
                        class="input branch-address-input"
                        type="text"
                        placeholder="New branch address"
                        autocomplete="off"
                    />

                    <button
                        type="button"
                        class="btn btn-outline remove-inline-branch"
                    >
                        ×
                    </button>
                `;


                wrapper.appendChild(row);

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Remove branch input
            |--------------------------------------------------------------------------
            */

                var removeButton =
                    event.target.closest(
                        ".remove-inline-branch"
                    );

                if (removeButton) {

                    var row =
                        removeButton.closest(
                            ".inline-branch-row"
                        );

                    if (row) {
                        row.remove();
                    }

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Save multiple branches
                |--------------------------------------------------------------------------
                */

                var saveButton =
                    event.target.closest(
                        ".save-inline-branches"
                    );

                if (!saveButton) {
                    return;
                }


                var workplaceId =
                    saveButton.dataset.id;


                var wrapper =
                    document.querySelector(
                        '.inline-branch-inputs[data-id="' +
                        workplaceId +
                        '"]'
                    );


                if (!wrapper) {
                    return;
                }


                var inputs =
                    wrapper.querySelectorAll(
                        ".branch-address-input"
                    );


                var branches = [];


                inputs.forEach(
                    function (input) {

                        var address =
                            input.value.trim();

                        if (address) {

                            branches.push(
                                address
                            );

                        }

                    }
                );


                if (branches.length === 0) {

                    toast(
                        "Please enter at least one branch address."
                    );

                    return;
                }


                saveButton.disabled = true;


                var csrf =
                    document.querySelector(
                        'meta[name="csrf-token"]'
                    );


                fetch(
                    "/admin/workplaces/" +
                    workplaceId +
                    "/branches",
                    {

                        method: "POST",

                        headers: {

                            "Accept":
                                "application/json",

                            "Content-Type":
                                "application/json",

                            "X-CSRF-TOKEN":
                                csrf
                                    ? csrf.getAttribute(
                                        "content"
                                    )
                                    : "",

                            "X-Requested-With":
                                "XMLHttpRequest"
                        },

                        body:
                            JSON.stringify({
                                branches: branches
                            })

                    }
                )

                .then(function (response) {

                    return response
                        .json()
                        .then(function (data) {

                            if (!response.ok) {

                                throw new Error(
                                    data.message ||
                                    "Unable to add branches."
                                );

                            }

                            return data;

                        });

                })

                .then(function (data) {

                    toast(
                        data.message
                    );

                    window.location.reload();

                })

                .catch(function (error) {

                    console.error(error);

                    toast(
                        error.message
                    );

                })

                .finally(function () {

                    saveButton.disabled = false;

                });

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Save company
        |--------------------------------------------------------------------------
        */

        if (saveButton) {

            saveButton.addEventListener(
                "click",
                function () {

                    var companyInput =
                        document.getElementById(
                            "company-name"
                        );

                    var branchInput =
                        document.getElementById(
                            "company-branch"
                        );


                    var companyName =
                        companyInput.value.trim();

                    var branchAddress =
                        branchInput.value.trim();


                    if (!companyName) {

                        toast(
                            "Please enter company name."
                        );

                        companyInput.focus();

                        return;
                    }


                    if (!branchAddress) {

                        toast(
                            "Please enter first branch address."
                        );

                        branchInput.focus();

                        return;
                    }


                    saveButton.disabled = true;


                    var csrf =
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        );


                    fetch(
                        "/admin/workplaces",
                        {

                            method: "POST",

                            headers: {

                                "Accept":
                                    "application/json",

                                "Content-Type":
                                    "application/json",

                                "X-CSRF-TOKEN":
                                    csrf
                                        ? csrf.getAttribute(
                                            "content"
                                        )
                                        : "",

                                "X-Requested-With":
                                    "XMLHttpRequest"

                            },

                            body: JSON.stringify({

                                company_name:
                                    companyName,

                                branch_address:
                                    branchAddress

                            })

                        }
                    )

                    .then(function (response) {

                        return response
                            .json()
                            .then(function (data) {

                                if (!response.ok) {

                                    throw new Error(
                                        data.message ||
                                        "Unable to add company."
                                    );

                                }

                                return data;

                            });

                    })

                    .then(function (data) {

                        toast(
                            data.message
                        );

                        modal.style.display =
                            "none";

                        window.location.reload();

                    })

                    .catch(function (error) {

                        console.error(error);

                        toast(
                            error.message
                        );

                    })

                    .finally(function () {

                        saveButton.disabled =
                            false;

                    });

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Delete Branch
        |--------------------------------------------------------------------------
        */
        list.addEventListener(
            "click",
            function (event) {

                var deleteButton =
                    event.target.closest(
                        ".delete-branch-btn"
                    );


                if (!deleteButton) {
                    return;
                }


                event.preventDefault();


                var branchId =
                    deleteButton.dataset.id;


                if (!branchId) {

                    toast(
                        "Branch id not found."
                    );

                    return;
                }


                if (
                    !confirm(
                        "Are you sure you want to delete this branch?"
                    )
                ) {
                    return;
                }


                deleteButton.disabled = true;


                var csrf =
                    document.querySelector(
                        'meta[name="csrf-token"]'
                    );


                fetch(
                    "/admin/workplace-branches/" +
                    branchId,
                    {

                        method: "DELETE",

                        headers: {

                            "Accept":
                                "application/json",

                            "X-CSRF-TOKEN":
                                csrf
                                    ? csrf.getAttribute(
                                        "content"
                                    )
                                    : "",

                            "X-Requested-With":
                                "XMLHttpRequest"

                        }

                    }
                )

                .then(function (response) {

                    return response
                        .json()
                        .then(function (data) {

                            if (!response.ok) {

                                throw new Error(
                                    data.message ||
                                    "Unable to delete branch."
                                );

                            }

                            return data;

                        });

                })

                .then(function (data) {

                    toast(
                        data.message
                    );


                    var branchRow =
                        deleteButton.closest(
                            ".branch"
                        );


                    var card =
                        deleteButton.closest(
                            "[data-workplace-id]"
                        );


                    if (branchRow) {

                        branchRow.remove();

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Update branch count
                    |--------------------------------------------------------------------------
                    */

                    if (card) {

                        var countElement =
                            card.querySelector(
                                ".branch-count"
                            );


                        var remainingBranches =
                            card.querySelectorAll(
                                ".branch-list .branch"
                            ).length;


                        if (countElement) {

                            countElement.textContent =
                                remainingBranches +
                                (
                                    remainingBranches === 1
                                        ? " branch"
                                        : " branches"
                                );

                        }

                    }

                })

                .catch(function (error) {

                    console.error(error);

                    toast(
                        error.message
                    );

                    deleteButton.disabled =
                        false;

                });

            }
        );

    }

    function initPayments() {

        var form =
            $("#payment-method-form");

        var list =
            $("#payment-method-list");

        if (!form || !list) {
            return;
        }


        function csrfToken() {

            var token =
                document.querySelector(
                    'meta[name="csrf-token"]'
                );

            return token
                ? token.getAttribute("content")
                : "";
        }


        /*
        |--------------------------------------------------------------------------
        | Add Payment Method
        |--------------------------------------------------------------------------
        */

        form.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();


                var input =
                    $("#mname");

                var button =
                    $("#add-payment-method-btn");

                var name =
                    input.value.trim();


                if (!name) {

                    input.focus();

                    toast(
                        "Please enter payment method name."
                    );

                    return;
                }


                button.disabled = true;


                fetch(
                    form.action ||
                    window.location.pathname,
                    {

                        method: "POST",

                        headers: {

                            "Accept":
                                "application/json",

                            "Content-Type":
                                "application/json",

                            "X-CSRF-TOKEN":
                                csrfToken(),

                            "X-Requested-With":
                                "XMLHttpRequest"
                        },

                        body:
                            JSON.stringify({
                                name: name
                            })
                    }
                )
                .then(function (response) {

                    return response.json()
                        .then(function (data) {

                            if (!response.ok) {

                                throw new Error(
                                    data.message ||
                                    "Unable to add payment method."
                                );
                            }

                            return data;
                        });

                })
                .then(function (data) {

                    toast(
                        data.message
                    );


                    /*
                    * Simplest and safest:
                    * reload current page after add
                    */
                    window.location.reload();

                })
                .catch(function (error) {

                    console.error(error);

                    toast(
                        error.message
                    );

                })
                .finally(function () {

                    button.disabled = false;

                });

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Active / Inactive Toggle
        |--------------------------------------------------------------------------
        */

        list.addEventListener(
            "click",
            function (event) {

                var button =
                    event.target.closest(
                        ".payment-toggle"
                    );


                if (!button) {
                    return;
                }


                var id =
                    button.dataset.id;


                button.disabled = true;


                fetch(
                    "/admin/payment-methods/" +
                    id +
                    "/toggle",
                    {

                        method: "POST",

                        headers: {

                            "Accept":
                                "application/json",

                            "Content-Type":
                                "application/json",

                            "X-CSRF-TOKEN":
                                csrfToken(),

                            "X-Requested-With":
                                "XMLHttpRequest"
                        }

                    }
                )
                .then(function (response) {

                    return response.json()
                        .then(function (data) {

                            if (!response.ok) {

                                throw new Error(
                                    data.message ||
                                    "Unable to update payment method."
                                );
                            }

                            return data;
                        });

                })
                .then(function (data) {

                    var active =
                        Boolean(
                            data.is_active
                        );


                    button.classList.toggle(
                        "on",
                        active
                    );


                    button.setAttribute(
                        "aria-checked",
                        String(active)
                    );


                    var card =
                        button.closest(
                            ".method"
                        );


                    var badge =
                        $(".status-badge", card);


                    if (badge) {

                        badge.textContent =
                            active
                                ? "Active"
                                : "Inactive";


                        badge.className =
                            "badge status-badge " +
                            (
                                active
                                    ? "tone-green"
                                    : "tone-red"
                            );
                    }


                    toast(
                        data.message
                    );

                })
                .catch(function (error) {

                    console.error(error);

                    toast(
                        error.message
                    );

                })
                .finally(function () {

                    button.disabled = false;

                });

            }
        );
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
