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

        var riderModal =
            document.getElementById(
                "rider-details-modal"
            );

        var riderContent =
            document.getElementById(
                "rider-details-content"
            );

        var riderClose =
            document.getElementById(
                "rider-modal-close"
            );

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

        /**
        * Detail view
        */

        document.addEventListener(
            "click",
            function (event) {

                var button =
                    event.target.closest(
                        ".rider-view-btn"
                    );


                if (!button) {
                    return;
                }


                event.preventDefault();


                var riderId =
                    button.dataset.id;


                if (!riderId) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Open Modal
                |--------------------------------------------------------------------------
                */

                riderModal.classList.add(
                    "open"
                );


                riderContent.innerHTML = `
                    <div
                        style="
                            padding:50px;
                            text-align:center;
                        "
                    >
                        Loading rider details...
                    </div>
                `;


                /*
                |--------------------------------------------------------------------------
                | Get Details
                |--------------------------------------------------------------------------
                */

                fetch(
                    "/admin/riders/" +
                    riderId +
                    "/details",
                    {

                        headers: {

                            "X-Requested-With":
                                "XMLHttpRequest",

                            "Accept":
                                "text/html"

                        }

                    }
                )

                .then(function (response) {

                    if (!response.ok) {

                        throw new Error(
                            "Unable to load rider details."
                        );

                    }


                    return response.text();

                })

                .then(function (html) {

                    riderContent.innerHTML =
                        html;

                })

                .catch(function (error) {

                    console.error(
                        error
                    );


                    riderContent.innerHTML = `
                        <div
                            style="
                                padding:40px;
                                text-align:center;
                            "
                        >
                            Unable to load rider details.
                        </div>
                    `;

                });

            }
        );

        /**
        *  close model
        */
        if (riderClose) {

            riderClose.addEventListener(
                "click",
                function () {

                    riderModal.classList.remove(
                        "open"
                    );

                }
            );

        }

         /**
        *  Close by clicking outside model
        */
        if (riderModal) {

            riderModal.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target ===
                        riderModal
                    ) {

                        riderModal.classList.remove(
                            "open"
                        );

                    }

                }
            );

        }

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

        var driverModal =
            document.getElementById(
                "driver-details-modal"
            );

        var driverContent =
            document.getElementById(
                "driver-details-content"
            );

        var driverClose =
            document.getElementById(
                "driver-modal-close"
            );    

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
        | View Details
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            "click",
            function (event) {

                var viewButton =
                    event.target.closest(
                        ".driver-view-btn"
                    );


                if (!viewButton) {
                    return;
                }


                var driverId =
                    viewButton.dataset.id;


                if (!driverId) {
                    return;
                }


                driverModal.classList.add(
                    "open"
                );


                driverContent.innerHTML = `
                    <div
                        style="
                            padding:50px;
                            text-align:center;
                        "
                    >
                        Loading driver details...
                    </div>
                `;


                fetch(
                    "/admin/drivers/" +
                    driverId +
                    "/details",
                    {
                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest",

                            "Accept":
                                "text/html"
                        }
                    }
                )

                .then(function (response) {

                    if (!response.ok) {

                        throw new Error(
                            "Unable to load driver details."
                        );

                    }

                    return response.text();

                })

                .then(function (html) {

                    driverContent.innerHTML =
                        html;

                })

                .catch(function (error) {

                    console.error(error);

                    driverContent.innerHTML = `
                        <div
                            style="
                                padding:40px;
                                text-align:center;
                            "
                        >
                            Unable to load driver details.
                        </div>
                    `;

                });

            }
        );


        /** cloase model */
        if (driverClose) {

                driverClose.addEventListener(
                    "click",
                    function () {

                        driverModal.classList.remove(
                            "open"
                        );

                    }
                );

        }


        if (driverModal) {

            driverModal.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target ===
                        driverModal
                    ) {

                        driverModal.classList.remove(
                            "open"
                        );

                    }

                }
            );

        }


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

        /*
        |--------------------------------------------------------------------------
        | Elements
        |--------------------------------------------------------------------------
        */

        var form =
            document.getElementById(
                "payment-method-form"
            );


        var list =
            document.getElementById(
                "payment-method-list"
            );


        var addButton =
            document.getElementById(
                "add-payment-method-btn"
            );


        var input =
            document.getElementById(
                "mname"
            );


        /*
        |--------------------------------------------------------------------------
        | Edit Modal
        |--------------------------------------------------------------------------
        */

        var editModal =
            document.getElementById(
                "payment-edit-modal"
            );


        var editId =
            document.getElementById(
                "edit-payment-id"
            );


        var editName =
            document.getElementById(
                "edit-payment-name"
            );


        var editCancel =
            document.getElementById(
                "edit-payment-cancel"
            );


        var editSave =
            document.getElementById(
                "edit-payment-save"
            );



        /*
        |--------------------------------------------------------------------------
        | CSRF
        |--------------------------------------------------------------------------
        */

        function csrfToken() {

            var token =
                document.querySelector(
                    'meta[name="csrf-token"]'
                );


            return token
                ? token.getAttribute(
                    "content"
                )
                : "";

        }



        /*
        |--------------------------------------------------------------------------
        | Add Payment Method
        |--------------------------------------------------------------------------
        */

        if (form) {

            form.addEventListener(
                "submit",
                function (event) {

                    event.preventDefault();


                    var name =
                        input.value.trim();


                    if (!name) {

                        toast(
                            "Please enter payment method name."
                        );


                        input.focus();

                        return;

                    }


                    addButton.disabled =
                        true;


                    fetch(
                        form.action,
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

                                    name:
                                        name

                                })

                        }
                    )

                    .then(
                        function (response) {

                            return response
                                .json()
                                .then(
                                    function (data) {

                                        if (
                                            !response.ok
                                        ) {

                                            throw new Error(
                                                data.message ||
                                                "Unable to add payment method."
                                            );

                                        }


                                        return data;

                                    }
                                );

                        }
                    )

                    .then(
                        function (data) {

                            toast(
                                data.message
                            );


                            /*
                            * Refresh because pagination
                            * and sort order may change.
                            */

                            window.location.reload();

                        }
                    )

                    .catch(
                        function (error) {

                            console.error(
                                error
                            );


                            toast(
                                error.message
                            );

                        }
                    )

                    .finally(
                        function () {

                            addButton.disabled =
                                false;

                        }
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Card Actions
        |--------------------------------------------------------------------------
        */

        if (list) {

            list.addEventListener(
                "click",
                function (event) {


                    /*
                    |--------------------------------------------------------------------------
                    | Toggle
                    |--------------------------------------------------------------------------
                    */

                    var toggleButton =
                        event.target.closest(
                            ".payment-toggle"
                        );


                    if (toggleButton) {

                        event.preventDefault();


                        var toggleId =
                            toggleButton
                                .dataset
                                .id;


                        if (!toggleId) {
                            return;
                        }


                        toggleButton.disabled =
                            true;


                        fetch(
                            "/admin/payment-methods/" +
                            toggleId +
                            "/toggle",
                            {

                                method:
                                    "POST",

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

                        .then(
                            function (response) {

                                return response
                                    .json()
                                    .then(
                                        function (data) {

                                            if (
                                                !response.ok
                                            ) {

                                                throw new Error(
                                                    data.message ||
                                                    "Unable to update payment method."
                                                );

                                            }


                                            return data;

                                        }
                                    );

                            }
                        )

                        .then(
                            function (data) {

                                var active =
                                    Boolean(
                                        data.is_active
                                    );


                                /*
                                * Toggle class
                                */

                                toggleButton
                                    .classList
                                    .toggle(
                                        "on",
                                        active
                                    );


                                toggleButton
                                    .setAttribute(
                                        "aria-checked",
                                        String(active)
                                    );


                                /*
                                * Status badge
                                */

                                var card =
                                    toggleButton.closest(
                                        ".method"
                                    );


                                if (card) {

                                    var badge =
                                        card.querySelector(
                                            ".status-badge"
                                        );


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

                                }


                                toast(
                                    data.message
                                );

                            }
                        )

                        .catch(
                            function (error) {

                                console.error(
                                    error
                                );


                                toast(
                                    error.message
                                );

                            }
                        )

                        .finally(
                            function () {

                                toggleButton.disabled =
                                    false;

                            }
                        );


                        return;

                    }



                    /*
                    |--------------------------------------------------------------------------
                    | Edit
                    |--------------------------------------------------------------------------
                    */

                    var editButton =
                        event.target.closest(
                            ".payment-method-edit"
                        );


                    if (editButton) {

                        event.preventDefault();


                        editId.value =
                            editButton
                                .dataset
                                .id;


                        editName.value =
                            editButton
                                .dataset
                                .name ||
                            "";


                        editModal
                            .classList
                            .add(
                                "open"
                            );


                        setTimeout(
                            function () {

                                editName.focus();

                                editName.select();

                            },
                            100
                        );


                        return;

                    }



                    /*
                    |--------------------------------------------------------------------------
                    | Delete
                    |--------------------------------------------------------------------------
                    */

                    var deleteButton =
                        event.target.closest(
                            ".payment-method-delete"
                        );


                    if (!deleteButton) {
                        return;
                    }


                    event.preventDefault();


                    var deleteId =
                        deleteButton
                            .dataset
                            .id;


                    if (!deleteId) {
                        return;
                    }


                    var confirmed =
                        confirm(
                            "Are you sure you want to delete this payment method?"
                        );


                    if (!confirmed) {
                        return;
                    }


                    deleteButton.disabled =
                        true;


                    fetch(
                        "/admin/payment-methods/" +
                        deleteId,
                        {

                            method:
                                "DELETE",

                            headers: {

                                "Accept":
                                    "application/json",

                                "X-CSRF-TOKEN":
                                    csrfToken(),

                                "X-Requested-With":
                                    "XMLHttpRequest"

                            }

                        }
                    )

                    .then(
                        function (response) {

                            return response
                                .json()
                                .then(
                                    function (data) {

                                        if (
                                            !response.ok
                                        ) {

                                            throw new Error(
                                                data.message ||
                                                "Unable to delete payment method."
                                            );

                                        }


                                        return data;

                                    }
                                );

                        }
                    )

                    .then(
                        function (data) {

                            toast(
                                data.message
                            );


                            /*
                            * Reload because current page
                            * may become empty after delete.
                            */

                            window.location.reload();

                        }
                    )

                    .catch(
                        function (error) {

                            console.error(
                                error
                            );


                            toast(
                                error.message
                            );


                            deleteButton.disabled =
                                false;

                        }
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Update Payment Method
        |--------------------------------------------------------------------------
        */

        if (editSave) {

            editSave.addEventListener(
                "click",
                function () {


                    var id =
                        editId.value;


                    var name =
                        editName
                            .value
                            .trim();


                    if (!id) {

                        toast(
                            "Payment method not found."
                        );

                        return;

                    }


                    if (!name) {

                        toast(
                            "Please enter payment method name."
                        );


                        editName.focus();

                        return;

                    }


                    editSave.disabled =
                        true;


                    fetch(
                        "/admin/payment-methods/" +
                        id +
                        "/update",
                        {

                            method:
                                "POST",

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

                                    name:
                                        name

                                })

                        }
                    )

                    .then(
                        function (response) {

                            return response
                                .json()
                                .then(
                                    function (data) {

                                        if (
                                            !response.ok
                                        ) {

                                            throw new Error(
                                                data.message ||
                                                "Unable to update payment method."
                                            );

                                        }


                                        return data;

                                    }
                                );

                        }
                    )

                    .then(
                        function (data) {

                            toast(
                                data.message
                            );


                            editModal
                                .classList
                                .remove(
                                    "open"
                                );


                            window.location.reload();

                        }
                    )

                    .catch(
                        function (error) {

                            console.error(
                                error
                            );


                            toast(
                                error.message
                            );

                        }
                    )

                    .finally(
                        function () {

                            editSave.disabled =
                                false;

                        }
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Cancel Edit
        |--------------------------------------------------------------------------
        */

        if (editCancel) {

            editCancel.addEventListener(
                "click",
                function () {

                    editModal
                        .classList
                        .remove(
                            "open"
                        );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Click Outside Modal
        |--------------------------------------------------------------------------
        */

        if (editModal) {

            editModal.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target ===
                        editModal
                    ) {

                        editModal
                            .classList
                            .remove(
                                "open"
                            );

                    }

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Escape Key
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key ===
                    "Escape"
                ) {

                    if (
                        editModal &&
                        editModal
                            .classList
                            .contains(
                                "open"
                            )
                    ) {

                        editModal
                            .classList
                            .remove(
                                "open"
                            );

                    }

                }

            }
        );

    }

    function initFareCalculations() {

        var modal =
            document.getElementById(
                "fare-level-modal"
            );


        var cards =
            document.getElementById(
                "fare-levels"
            );


        var cancelButton =
            document.getElementById(
                "fare-modal-cancel"
            );


        var saveButton =
            document.getElementById(
                "fare-modal-save"
            );


        var idInput =
            document.getElementById(
                "fare-level-id"
            );


        var levelNameDisplay =
            document.getElementById(
                "fare-level-name-display"
            );


        var rangeDisplay =
            document.getElementById(
                "fare-range-display"
            );


        var baseInput =
            document.getElementById(
                "fare-base-price"
            );


        var rateInput =
            document.getElementById(
                "fare-price-mile"
            );


        var distanceInput =
            document.getElementById(
                "fare-distance"
            );


        var previewValue =
            document.getElementById(
                "fare-preview-value"
            );


        var previewLevel =
            document.getElementById(
                "fare-preview-level"
            );



        /*
        |--------------------------------------------------------------------------
        | CSRF
        |--------------------------------------------------------------------------
        */

        function csrfToken() {

            var token =
                document.querySelector(
                    'meta[name="csrf-token"]'
                );


            return token
                ? token.getAttribute(
                    "content"
                )
                : "";

        }



        /*
        |--------------------------------------------------------------------------
        | Money Formatter
        |--------------------------------------------------------------------------
        */

        function money(value) {

            return new Intl.NumberFormat(
                "en-US",
                {
                    style:
                        "currency",

                    currency:
                        "USD"
                }
            ).format(value);

        }



        /*
        |--------------------------------------------------------------------------
        | Find Fare Level
        |--------------------------------------------------------------------------
        */

        function findFareLevel(
            miles
        ) {

            var levels =
                window.fareLevels || [];


            if (
                miles >= 0.10 &&
                miles <= 5.00
            ) {

                return levels.find(
                    function (level) {

                        return Number(
                            level.level_no
                        ) === 1;

                    }
                );

            }


            if (
                miles >= 5.01 &&
                miles <= 10.00
            ) {

                return levels.find(
                    function (level) {

                        return Number(
                            level.level_no
                        ) === 2;

                    }
                );

            }


            if (
                miles > 10.00
            ) {

                return levels.find(
                    function (level) {

                        return Number(
                            level.level_no
                        ) === 3;

                    }
                );

            }


            return null;

        }



        /*
        |--------------------------------------------------------------------------
        | Calculate Fare
        |--------------------------------------------------------------------------
        */

        function calculateFare(
            miles
        ) {

            var level =
                findFareLevel(
                    miles
                );


            if (!level) {

                return {
                    fare: 0,
                    level: null
                };

            }


            var base =
                Number(
                    level.base_price
                );


            var rate =
                Number(
                    level.price_per_mile
                );

            // Fare = $ fixed amount charged for pickup + ($ amount/mile x distance).
            // Case 1:
            //     Distance for rider A is 5.5 miles:
            //     Fare for rider A will be calculated as follows 

            //     Fare = $0.75 + (5.5 x $ 1.00)
            //     Fare = $ 6.25

            // Case 2:
            //     Distance for rider B is 7.8 miles:
            //     Fare for rider B will be calculated as follows 

            //     Fare =$0.75 + (7.8 x $1.00)
            //     Fare =$8.55

            var fare =
                base +
                (
                    miles *
                    rate
                );


            return {
                fare:
                    Math.round(
                        fare * 100
                    ) / 100,

                level:
                    level
            };

        }



        /*
        |--------------------------------------------------------------------------
        | Preview
        |--------------------------------------------------------------------------
        */

        function preview() {

            if (
                !distanceInput ||
                !previewValue
            ) {
                return;
            }


            var miles =
                Number(
                    distanceInput.value
                );


            if (
                distanceInput.value === "" ||
                !Number.isFinite(
                    miles
                ) ||
                miles <= 0
            ) {

                previewValue.textContent =
                    "$0.00";


                if (previewLevel) {

                    previewLevel.textContent =
                        "";

                }

                return;
            }


            var calculation =
                calculateFare(
                    miles
                );


            previewValue.textContent =
                money(
                    calculation.fare
                );


            if (previewLevel) {

                if (
                    calculation.level
                ) {

                    previewLevel.textContent =
                        calculation
                            .level
                            .level_name;

                } else {

                    previewLevel.textContent =
                        "No fare level";

                }

            }

        }



        /*
        |--------------------------------------------------------------------------
        | Open Edit Modal
        |--------------------------------------------------------------------------
        */

        if (cards) {

            cards.addEventListener(
                "click",
                function (event) {

                    var button =
                        event.target.closest(
                            "[data-edit]"
                        );


                    if (!button) {
                        return;
                    }


                    idInput.value =
                        button.dataset.id;


                    levelNameDisplay
                        .textContent =
                        button.dataset.name;


                    rangeDisplay
                        .textContent =
                        button.dataset.range;


                    baseInput.value =
                        button.dataset.base;


                    rateInput.value =
                        button.dataset.rate;


                    modal.classList.add(
                        "open"
                    );


                    setTimeout(
                        function () {

                            baseInput.focus();

                            baseInput.select();

                        },
                        100
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Close Modal
        |--------------------------------------------------------------------------
        */

        if (cancelButton) {

            cancelButton.addEventListener(
                "click",
                function () {

                    modal.classList.remove(
                        "open"
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Close Outside
        |--------------------------------------------------------------------------
        */

        if (modal) {

            modal.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target ===
                        modal
                    ) {

                        modal.classList.remove(
                            "open"
                        );

                    }

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        if (saveButton) {

            saveButton.addEventListener(
                "click",
                function () {

                    var id =
                        idInput.value;


                    var base =
                        Number(
                            baseInput.value
                        );


                    var rate =
                        Number(
                            rateInput.value
                        );


                    if (!id) {

                        toast(
                            "Fare level not found."
                        );

                        return;
                    }


                    if (
                        baseInput.value === "" ||
                        !Number.isFinite(
                            base
                        ) ||
                        base < 0
                    ) {

                        toast(
                            "Please enter a valid fixed pickup amount."
                        );

                        baseInput.focus();

                        return;
                    }


                    if (
                        rateInput.value === "" ||
                        !Number.isFinite(
                            rate
                        ) ||
                        rate < 0
                    ) {

                        toast(
                            "Please enter a valid price per mile."
                        );

                        rateInput.focus();

                        return;
                    }


                    saveButton.disabled =
                        true;


                    fetch(
                        "/admin/fare-calculations/" +
                        id +
                        "/update",
                        {

                            method:
                                "POST",

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

                                    base_price:
                                        base,

                                    price_per_mile:
                                        rate

                                })

                        }
                    )

                    .then(
                        function (response) {

                            return response
                                .json()
                                .then(
                                    function (
                                        data
                                    ) {

                                        if (
                                            !response.ok
                                        ) {

                                            throw new Error(
                                                data.message ||
                                                "Unable to update fare level."
                                            );

                                        }


                                        return data;

                                    }
                                );

                        }
                    )

                    .then(
                        function (data) {

                            toast(
                                data.message
                            );


                            modal.classList.remove(
                                "open"
                            );


                            window.location.reload();

                        }
                    )

                    .catch(
                        function (error) {

                            console.error(
                                error
                            );


                            toast(
                                error.message
                            );

                        }
                    )

                    .finally(
                        function () {

                            saveButton.disabled =
                                false;

                        }
                    );

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Preview Event
        |--------------------------------------------------------------------------
        */

        if (distanceInput) {

            distanceInput.addEventListener(
                "input",
                preview
            );


            preview();

        }

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
            "fare-calculations": initFareCalculations,
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
