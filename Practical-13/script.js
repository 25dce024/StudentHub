/**
 * StudentHub - Practical 13: Asynchronous AJAX/Fetch CRUD Engine
 * Subject: Web Development Frameworks (ITUE203)
 * CHARUSAT University - Semester 3
 */

document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const studentTableBody = document.getElementById('studentTableBody');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const searchInput = document.getElementById('searchInput');
    const departmentFilter = document.getElementById('departmentFilter');
    const statusFilter = document.getElementById('statusFilter');
    const refreshBtn = document.getElementById('refreshBtn');
    const openAddModalBtn = document.getElementById('openAddModalBtn');

    // Stat Elements
    const totalCountEl = document.getElementById('totalCount');
    const activeCountEl = document.getElementById('activeCount');
    const avgCgpaEl = document.getElementById('avgCgpa');

    // Modal Elements
    const studentModal = document.getElementById('studentModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');
    const studentForm = document.getElementById('studentForm');
    const submitBtn = document.getElementById('submitBtn');
    const formErrorAlert = document.getElementById('formErrorAlert');

    // Hidden & Input Form Fields
    const studentDbId = document.getElementById('studentDbId');
    const studentIdInput = document.getElementById('studentIdInput');
    const nameInput = document.getElementById('nameInput');
    const emailInput = document.getElementById('emailInput');
    const mobileInput = document.getElementById('mobileInput');
    const branchInput = document.getElementById('branchInput');
    const semesterInput = document.getElementById('semesterInput');
    const academicYearInput = document.getElementById('academicYearInput');
    const cgpaInput = document.getElementById('cgpaInput');
    const statusInput = document.getElementById('statusInput');
    const addressInput = document.getElementById('addressInput');

    const API_URL = 'api/students.php';
    let debounceTimer = null;

    // ----------------------------------------------------------------------
    // Toast Notification System
    // ----------------------------------------------------------------------
    function showToast(message, type = 'success', duration = 3500) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = type === 'success' ? '&#10004;' : (type === 'error' ? '&#9888;' : '&#8505;');
        toast.innerHTML = `<span style="font-size: 1.1rem;">${icon}</span> <span>${escapeHtml(message)}</span>`;

        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // ----------------------------------------------------------------------
    // Asynchronous Data Fetching & Rendering
    // ----------------------------------------------------------------------
    async function loadStudents() {
        loadingOverlay.classList.add('active');
        
        const params = new URLSearchParams();
        if (searchInput.value.trim()) params.append('search', searchInput.value.trim());
        if (departmentFilter.value && departmentFilter.value !== 'ALL') params.append('branch', departmentFilter.value);
        if (statusFilter.value && statusFilter.value !== 'ALL') params.append('status', statusFilter.value);

        try {
            const res = await fetch(`${API_URL}?${params.toString()}`);
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: Failed to fetch student data`);
            }
            const json = await res.json();
            
            if (json.success) {
                renderTable(json.data.students || []);
                updateStats(json.data.students || []);
            } else {
                showToast(json.message || 'Error loading students', 'error');
            }
        } catch (err) {
            console.error('Fetch error:', err);
            showToast('Unable to connect to StudentHub API. Please check server.', 'error');
            studentTableBody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 36px; color: var(--danger);">
                        &#9888; Failed to communicate with PHP API (<code>${API_URL}</code>). Ensure XAMPP MySQL is active.
                    </td>
                </tr>
            `;
        } finally {
            loadingOverlay.classList.remove('active');
        }
    }

    function renderTable(students) {
        if (!students.length) {
            studentTableBody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-muted);">
                        No students found matching current filter parameters.
                    </td>
                </tr>
            `;
            return;
        }

        studentTableBody.innerHTML = students.map(st => `
            <tr id="student-row-${st.id}">
                <td><code>${escapeHtml(st.student_id)}</code></td>
                <td><strong>${escapeHtml(st.name)}</strong></td>
                <td>${escapeHtml(st.email)}</td>
                <td>
                    ${escapeHtml(st.branch)}
                    <span class="badge" style="font-size: 11px; padding: 2px 6px;">Sem ${st.semester}</span>
                </td>
                <td>${escapeHtml(st.mobile)}</td>
                <td>
                    <span class="badge" style="background-color: #fef3c7; color: #92400e; font-weight: 700;">
                        &#11088; ${parseFloat(st.cgpa || 0).toFixed(2)}
                    </span>
                </td>
                <td>
                    <span class="badge-status status-${(st.status || 'active').toLowerCase()}">
                        ${escapeHtml(st.status || 'active')}
                    </span>
                </td>
                <td style="text-align: right;">
                    <div class="action-links">
                        <button class="btn-edit" data-id="${st.id}">&#9998; Edit</button>
                        <button class="btn-del" data-id="${st.id}" data-name="${escapeHtml(st.name)}" data-sid="${escapeHtml(st.student_id)}">&#128465; Delete</button>
                    </div>
                </td>
            </tr>
        `).join('');

        // Attach action button event listeners
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', () => openEditModal(btn.dataset.id));
        });

        document.querySelectorAll('.btn-del').forEach(btn => {
            btn.addEventListener('click', () => deleteStudent(btn.dataset.id, btn.dataset.name, btn.dataset.sid));
        });
    }

    function updateStats(students) {
        totalCountEl.textContent = students.length;
        const activeCount = students.filter(s => (s.status || '').toLowerCase() === 'active').length;
        activeCountEl.textContent = activeCount;

        if (students.length > 0) {
            const totalCgpa = students.reduce((acc, curr) => acc + parseFloat(curr.cgpa || 0), 0);
            avgCgpaEl.textContent = (totalCgpa / students.length).toFixed(2);
        } else {
            avgCgpaEl.textContent = '0.00';
        }
    }

    // ----------------------------------------------------------------------
    // Filter Event Listeners
    // ----------------------------------------------------------------------
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadStudents, 250);
    });

    departmentFilter.addEventListener('change', loadStudents);
    statusFilter.addEventListener('change', loadStudents);
    refreshBtn.addEventListener('click', () => {
        loadStudents();
        showToast('Student list refreshed', 'info');
    });

    // ----------------------------------------------------------------------
    // Modal Open / Close Handlers
    // ----------------------------------------------------------------------
    function openCreateModal() {
        studentForm.reset();
        studentDbId.value = '';
        modalTitle.textContent = 'Add New Student';
        submitBtn.innerHTML = '&#10004; Save Student';
        formErrorAlert.style.display = 'none';
        studentModal.classList.add('active');
        studentIdInput.focus();
    }

    async function openEditModal(id) {
        studentForm.reset();
        studentDbId.value = id;
        modalTitle.textContent = `Edit Student #${id}`;
        submitBtn.innerHTML = '&#10004; Update Student';
        formErrorAlert.style.display = 'none';

        try {
            const res = await fetch(`${API_URL}?id=${id}`);
            const json = await res.json();
            if (json.success && json.data) {
                const s = json.data;
                studentIdInput.value = s.student_id;
                nameInput.value = s.name;
                emailInput.value = s.email;
                mobileInput.value = s.mobile;
                branchInput.value = s.branch;
                semesterInput.value = s.semester;
                academicYearInput.value = s.academic_year;
                cgpaInput.value = s.cgpa;
                statusInput.value = s.status;
                addressInput.value = s.address || '';

                studentModal.classList.add('active');
                nameInput.focus();
            } else {
                showToast(json.message || 'Failed to fetch student record', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Network error loading student details', 'error');
        }
    }

    function closeModal() {
        studentModal.classList.remove('active');
    }

    openAddModalBtn.addEventListener('click', openCreateModal);
    modalCloseBtn.addEventListener('click', closeModal);
    cancelModalBtn.addEventListener('click', closeModal);
    studentModal.addEventListener('click', (e) => {
        if (e.target === studentModal) closeModal();
    });

    // ----------------------------------------------------------------------
    // Submit Form Handler (Create or Update Asynchronously)
    // ----------------------------------------------------------------------
    studentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        formErrorAlert.style.display = 'none';
        
        const isEdit = Boolean(studentDbId.value);
        const payload = {
            id: studentDbId.value ? parseInt(studentDbId.value) : undefined,
            student_id: studentIdInput.value.trim().toUpperCase(),
            name: nameInput.value.trim(),
            email: emailInput.value.trim(),
            mobile: mobileInput.value.trim(),
            course: 'Computer Engineering',
            branch: branchInput.value,
            semester: parseInt(semesterInput.value),
            academic_year: academicYearInput.value,
            cgpa: parseFloat(cgpaInput.value),
            status: statusInput.value,
            address: addressInput.value.trim()
        };

        const method = isEdit ? 'PUT' : 'POST';
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '&#8987; Processing...';
        submitBtn.disabled = true;

        try {
            const res = await fetch(API_URL, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const json = await res.json();

            if (json.success) {
                closeModal();
                showToast(json.message || (isEdit ? 'Student updated successfully!' : 'Student created successfully!'), 'success');
                loadStudents();
            } else {
                const errorMsg = json.errors ? json.errors.join('<br>') : (json.message || 'Operation failed');
                formErrorAlert.innerHTML = `&#9888; ${errorMsg}`;
                formErrorAlert.style.display = 'block';
                showToast(json.message || 'Validation error', 'error');
            }
        } catch (err) {
            console.error('Submit error:', err);
            formErrorAlert.innerHTML = '&#9888; Connection failure to PHP API.';
            formErrorAlert.style.display = 'block';
            showToast('Failed to submit data to server', 'error');
        } finally {
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        }
    });

    // ----------------------------------------------------------------------
    // Asynchronous Deletion Handler
    // ----------------------------------------------------------------------
    async function deleteStudent(id, name, studentId) {
        const confirmed = window.confirm(`Are you sure you want to delete student "${name}" (${studentId})?\n\nThis will be executed asynchronously without page reload.`);
        if (!confirmed) return;

        try {
            const res = await fetch(`${API_URL}?id=${id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            });

            const json = await res.json();

            if (json.success) {
                showToast(json.message || `Student #${id} deleted`, 'success');
                // Smooth row removal animation
                const row = document.getElementById(`student-row-${id}`);
                if (row) {
                    row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'scale(0.95)';
                    setTimeout(loadStudents, 300);
                } else {
                    loadStudents();
                }
            } else {
                showToast(json.message || 'Failed to delete student', 'error');
            }
        } catch (err) {
            console.error('Delete error:', err);
            showToast('Network error while deleting record', 'error');
        }
    }

    // Initial Load
    loadStudents();
});
