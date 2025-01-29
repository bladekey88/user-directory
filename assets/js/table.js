

function generateTable() {
    // Create Table Options
    const options = {
        valueNames: ['user-id', 'username', 'name', 'commonname', 'house', 'year', 'role', 'date-updated'],
        page: 10,
        searchClass: 'table-search',
        pagination: [{
            paginationClass: 'pagination-top',
            innerWindow: 3,
            outerWindow: 1
        }]
    };

    // Generate the List object from Table
    const userList = new List('user-details', options);
    const tableSize = document.querySelector('.table-size');
    tableSize.innerHTML = `Table consists of ${userList.size()} ${userList.size() === 1 ? 'record' : 'records'}`

    // Update table size message based on filtered items
    userList.on('updated', () => {
        tableSize.innerHTML = `Table consists of ${userList.matchingItems.length} ${userList.matchingItems.length === 1 ? 'record' : 'records'} ${userList.matchingItems.length !== userList.size() ? `(filtered from ${userList.size()} records)` : ''}`;
    });

    // Generate Navigation
    const tableNavigation = document.querySelector('.pagination-top');
    tableNavigation.insertAdjacentHTML('beforeBegin', '<div class="btn-first" role="button" aria-pressed="false" tabindex="0"> << </div><div class="btn-prev" role="button" aria-pressed="false" tabindex="0"> < </div>');
    tableNavigation.insertAdjacentHTML('afterEnd', '<div class="btn-next" role="button" aria-pressed="false" tabindex="0"> > </div><div class="btn-last" role="button" aria-pressed="false" tabindex="0"> >> </div>');

    const btnFirst = document.querySelector('.btn-first');
    const btnPrev = document.querySelector('.btn-prev');
    const btnNext = document.querySelector('.btn-next');
    const btnLast = document.querySelector('.btn-last');

    btnFirst.addEventListener('click', () => userList.show(1, options.page));
    btnLast.addEventListener('click', () => userList.show((Math.floor(userList.size() / options.page) * options.page) + 1, options.page));
    btnNext.addEventListener('click', () => handlePageChange('next'));
    btnPrev.addEventListener('click', () => handlePageChange('prev'));

    const searchFormClass = userList.searchClass;
    const searchForm = document.querySelector(`.${searchFormClass} `);
    const tableControls = document.querySelector('.table-controls');
    const tableFilterControls = document.querySelector(".table-filter-controls");

    // Local storage for search form
    searchForm.addEventListener("input", (e) => { console.debug("Search Value: " + e.target.value); localStorage.setItem("HG-search-form", e.target.value) })


    // Remove hidden class to show all controls
    tableControls.classList.remove("no-js");
    tableFilterControls.classList.remove("no-js");

    // If there is an unknown user link, activate it when the user clicks
    const linkNoRole = document.querySelector(".link-table-search-users-no-role");
    if (linkNoRole) {
        linkNoRole.addEventListener("click", () => searchUnknownRole());
    }

    // If there is a locked account link, activate it when the user clicks
    const linkLockedAccount = document.querySelector(".link-table-search-users-locked");
    if (linkLockedAccount) {
        linkLockedAccount.addEventListener("click", () => searchLockedAccount());
    }

    function handlePageChange(direction) {
        const tableNavigationActive = document.querySelector('.pagination-top .active');
        const targetPage = direction === 'next' ? tableNavigationActive.nextElementSibling : tableNavigationActive.previousElementSibling;

        if (targetPage) {
            const pageNumber = targetPage.childNodes[0].getAttribute('data-i');
            const pageSize = targetPage.childNodes[0].getAttribute('data-page');
            const startingRecord = ((pageNumber - 1) * pageSize) + 1;

            userList.show(startingRecord, pageSize);
            tableNavigationActive.classList.remove('active');
            targetPage.classList.add('active');
        }
    }

    // Call appropriate filters
    filterParents();
    filterStaff();
    filterStudents();
    clearFilter();
    document.addEventListener("DOMContentLoaded", (event) => {
        // If there is a value set in local storage, retrieve it and apply it
        searchForm.value = localStorage.getItem("HG-search-form");
        if (searchForm.value) { userList.search(searchForm.value); }

    })

    function filterByRole(role) {
        const filterButton = document.querySelector(`[name="Filter ${role}"]`);

        filterButton.addEventListener('click', e => {
            let allFilterButtons = e.target.parentElement.children;
            for (let btn of allFilterButtons) {
                if (btn.name !== "Clear Filter") {
                    btn.classList.remove("active");
                    let filterIcon = (btn.querySelector('img'))
                    filterIcon.setAttribute("src", "assets/img/icon-filter-off.svg");
                    btn.setAttribute("aria-pressed", "false");
                }
            }
            e.target.setAttribute("aria-pressed", "true");
            e.target.classList.add("active");
            let filterIcon = (e.target.querySelector('img'))
            filterIcon.src = "assets/img/icon-filter-on.svg";
            userList.filter();
            userList.filter(item => item.values().role.includes(role));
        });
    }

    function filterParents() {
        filterByRole('Parent');
    }

    function filterStudents() {
        filterByRole('Student');
    }

    function filterStaff() {
        filterByRole('Staff');
    }

    function clearFilter() {
        const filterButton = document.querySelector('[name="Clear Filter"]');

        filterButton.addEventListener('click', () => {
            const appliedFilterButtons = document.querySelectorAll(`[name^="Filter"]`);
            userList.filter();
            for (let btn of appliedFilterButtons) {
                btn.classList.remove("active");
                let filterIcon = (btn.querySelector('img'))
                filterIcon.src = "assets/img/icon-filter-off.svg";
                btn.setAttribute("aria-pressed", "false");
                searchForm.value = null;
                localStorage.removeItem("HG-search-form");
                userList.search();
            }
        });
    }

    function searchUnknownRole() {
        const UnknownRole = '"Role: Unknown"';
        searchForm.value = UnknownRole;
        localStorage.setItem("HG-search-form", UnknownRole);
        userList.search(UnknownRole);

    }

    function searchLockedAccount() {
        const AccountLocked = '"Account Locked"';
        searchForm.value = AccountLocked;
        userList.search(AccountLocked);
        localStorage.setItem("HG-search-form", AccountLocked);

    }

}



generateTable();