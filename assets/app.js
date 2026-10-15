$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    let selectedExamId = null;
    let editingExamId = null;
    let editingQuestionId = null;
    let exams = [];
    let toastTimer = null;

    function request(action, values) {
        return $.ajax({
            url: 'api.php',
            method: 'POST',
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            headers: { 'X-CSRF-Token': csrfToken },
            data: JSON.stringify(Object.assign({ action: action }, values || {}))
        });
    }

    function showError(xhr) {
        const message = xhr.responseJSON && xhr.responseJSON.error
            ? xhr.responseJSON.error
            : 'No se pudo completar la solicitud. Revisá tu conexión e intentá nuevamente.';
        showToast(message, true);
    }

    function showToast(message, isError) {
        window.clearTimeout(toastTimer);
        $('#toast').text(message).toggleClass('toast-error', Boolean(isError)).prop('hidden', false);
        toastTimer = window.setTimeout(function () {
            $('#toast').prop('hidden', true);
        }, 3800);
    }

    function setAuthenticated(name) {
        $('#auth-panel').prop('hidden', true);
        $('#app-panel, #topbar-user').prop('hidden', false);
        $('#welcome-name').text('Hola, ' + name);
        loadExams();
    }

    function setUnauthenticated() {
        selectedExamId = null;
        $('#app-panel, #topbar-user').prop('hidden', true);
        $('#auth-panel').prop('hidden', false);
    }

    function selectAuthTab(tab) {
        const registering = tab === 'register';
        $('#login-tab').toggleClass('is-active', !registering).attr('aria-selected', String(!registering));
        $('#register-tab').toggleClass('is-active', registering).attr('aria-selected', String(registering));
        $('#login-form').prop('hidden', registering);
        $('#register-form').prop('hidden', !registering);
    }

    function loadExams(preferredId) {
        request('list_exams').done(function (response) {
            exams = response.exams || [];
            renderExams();
            const nextId = preferredId || selectedExamId;
            if (nextId && exams.some(function (exam) { return Number(exam.id) === Number(nextId); })) {
                selectExam(nextId);
            } else if (exams.length > 0) {
                selectExam(exams[0].id);
            } else {
                selectedExamId = null;
                $('#exam-detail').prop('hidden', true);
                $('#select-exam-message').prop('hidden', false);
                $('#question-list').empty();
                $('#draw-result').prop('hidden', true);
            }
        }).fail(showError);
    }

    function renderExams() {
        const list = $('#exam-list').empty();
        $('#exam-total').text(exams.length + (exams.length === 1 ? ' examen' : ' exámenes'));
        $('#empty-exams').prop('hidden', exams.length !== 0);

        exams.forEach(function (exam) {
            const button = $('<button>', {
                type: 'button',
                class: 'exam-item',
                'data-exam-id': exam.id
            });
            const copy = $('<span>', { class: 'exam-item-copy' })
                .append($('<strong>').text(exam.title))
                .append($('<span>').text(Number(exam.question_count) + (Number(exam.question_count) === 1 ? ' pregunta' : ' preguntas')));
            button.append($('<span>', { class: 'exam-item-icon', text: '▤' }), copy, $('<span>', { class: 'exam-item-arrow', text: '›' }));
            button.toggleClass('is-selected', Number(exam.id) === Number(selectedExamId));
            list.append(button);
        });
    }

    function selectExam(id) {
        const exam = exams.find(function (item) { return Number(item.id) === Number(id); });
        if (!exam) {
            return;
        }
        selectedExamId = Number(exam.id);
        renderExams();
        $('#select-exam-message').prop('hidden', true);
        $('#exam-detail').prop('hidden', false);
        $('#selected-exam-title').text(exam.title);
        $('#selected-exam-description').text(exam.description || 'Sin descripción').toggle(Boolean(exam.description));
        $('#draw-result').prop('hidden', true);
        loadQuestions();
    }

    function loadQuestions() {
        if (!selectedExamId) {
            return;
        }
        request('list_questions', { exam_id: selectedExamId }).done(function (response) {
            renderQuestions(response.questions || []);
        }).fail(showError);
    }

    function renderQuestions(questions) {
        const list = $('#question-list').empty();
        $('#question-total').text(questions.length + (questions.length === 1 ? ' pregunta' : ' preguntas'));
        $('#available-count').text('de ' + questions.length);
        $('#draw-count').attr('max', Math.max(questions.length, 1));
        $('#empty-questions').prop('hidden', questions.length !== 0);

        questions.forEach(function (question) {
            const item = $('<article>', { class: 'question-item' });
            const copy = $('<div>', { class: 'question-item-copy' })
                .append($('<p>').text(question.question_text));
            if (question.answer_text) {
                copy.append($('<details>').append($('<summary>').text('Ver respuesta')).append($('<p>').text(question.answer_text)));
            }
            const remove = $('<button>', {
                type: 'button',
                class: 'icon-button delete-question-button',
                title: 'Eliminar pregunta',
                'aria-label': 'Eliminar pregunta',
                'data-question-id': question.id,
                text: '×'
            });
            const edit = $('<button>', {
                type: 'button',
                class: 'icon-button edit-question-button',
                title: 'Editar pregunta',
                'aria-label': 'Editar pregunta',
                'data-question-id': question.id,
                'data-question-text': question.question_text,
                'data-answer-text': question.answer_text || '',
                text: '✎'
            });
            list.append(item.append(copy, $('<div>', { class: 'question-actions' }).append(edit, remove)));
        });
    }

    $('#login-tab').on('click', function () { selectAuthTab('login'); });
    $('#register-tab').on('click', function () { selectAuthTab('register'); });

    $('#login-form').on('submit', function (event) {
        event.preventDefault();
        const values = Object.fromEntries(new FormData(this).entries());
        request('login', values).done(function (response) {
            setAuthenticated(response.user.name);
            $('#login-form')[0].reset();
        }).fail(showError);
    });

    $('#register-form').on('submit', function (event) {
        event.preventDefault();
        const values = Object.fromEntries(new FormData(this).entries());
        request('register', values).done(function (response) {
            setAuthenticated(response.user.name);
            $('#register-form')[0].reset();
        }).fail(showError);
    });

    $('#logout-button').on('click', function () {
        request('logout').done(function () {
            setUnauthenticated();
            showToast('Cerraste sesión correctamente.');
        }).fail(showError);
    });

    $('#new-exam-button, #empty-new-exam-button').on('click', function () {
        editingExamId = null;
        $('#exam-form')[0].reset();
        $('#exam-dialog-title').text('Creá un examen');
        $('#save-exam-button').text('Crear examen');
        $('#exam-dialog .eyebrow').text('NUEVO BANCO');
        $('#exam-dialog')[0].showModal();
        $('#exam-title').trigger('focus');
    });

    $('#edit-exam-button').on('click', function () {
        const exam = exams.find(function (item) { return Number(item.id) === Number(selectedExamId); });
        if (!exam) {
            return;
        }
        editingExamId = Number(exam.id);
        $('#exam-form')[0].reset();
        $('#exam-title').val(exam.title);
        $('#exam-description').val(exam.description || '');
        $('#exam-dialog-title').text('Editar examen');
        $('#save-exam-button').text('Guardar cambios');
        $('#exam-dialog .eyebrow').text('BANCO DE PREGUNTAS');
        $('#exam-dialog')[0].showModal();
        $('#exam-title').trigger('focus');
    });

    $('#close-exam-dialog, #cancel-exam-dialog').on('click', function () {
        $('#exam-dialog')[0].close();
    });

    $('#exam-form').on('submit', function (event) {
        event.preventDefault();
        const values = Object.fromEntries(new FormData(this).entries());
        const action = editingExamId ? 'update_exam' : 'create_exam';
        if (editingExamId) {
            values.exam_id = editingExamId;
        }
        request(action, values).done(function (response) {
            const examId = editingExamId || response.id;
            editingExamId = null;
            $('#exam-dialog')[0].close();
            $('#exam-form')[0].reset();
            showToast(action === 'create_exam' ? 'El examen se creó correctamente.' : 'El examen se actualizó.');
            loadExams(examId);
        }).fail(showError);
    });

    $('#exam-list').on('click', '.exam-item', function () {
        selectExam($(this).data('exam-id'));
    });

    $('#question-form').on('submit', function (event) {
        event.preventDefault();
        if (!selectedExamId) {
            return;
        }
        const values = Object.fromEntries(new FormData(this).entries());
        values.exam_id = selectedExamId;
        request('create_question', values).done(function () {
            $('#question-form')[0].reset();
            $('#draw-result').prop('hidden', true);
            showToast('La pregunta se agregó al banco.');
            loadQuestions();
            loadExams(selectedExamId);
        }).fail(showError);
    });

    $('#question-list').on('click', '.delete-question-button', function () {
        const questionId = $(this).data('question-id');
        if (!window.confirm('¿Querés eliminar esta pregunta?')) {
            return;
        }
        request('delete_question', { exam_id: selectedExamId, question_id: questionId }).done(function () {
            showToast('La pregunta se eliminó.');
            loadQuestions();
            loadExams(selectedExamId);
        }).fail(showError);
    });

    $('#question-list').on('click', '.edit-question-button', function () {
        editingQuestionId = Number($(this).data('question-id'));
        $('#edit-question-text').val($(this).attr('data-question-text'));
        $('#edit-answer-text').val($(this).attr('data-answer-text'));
        $('#question-dialog')[0].showModal();
        $('#edit-question-text').trigger('focus');
    });

    $('#close-question-dialog, #cancel-question-dialog').on('click', function () {
        $('#question-dialog')[0].close();
        editingQuestionId = null;
    });

    $('#question-edit-form').on('submit', function (event) {
        event.preventDefault();
        if (!selectedExamId || !editingQuestionId) {
            return;
        }
        const values = Object.fromEntries(new FormData(this).entries());
        values.exam_id = selectedExamId;
        values.question_id = editingQuestionId;
        request('update_question', values).done(function () {
            $('#question-dialog')[0].close();
            editingQuestionId = null;
            showToast('La pregunta se actualizó.');
            loadQuestions();
        }).fail(showError);
    });

    $('#delete-exam-button').on('click', function () {
        const exam = exams.find(function (item) { return Number(item.id) === Number(selectedExamId); });
        if (!exam || !window.confirm('¿Eliminar "' + exam.title + '" y todas sus preguntas? Esta acción no se puede deshacer.')) {
            return;
        }
        request('delete_exam', { exam_id: selectedExamId }).done(function () {
            selectedExamId = null;
            showToast('El examen y sus preguntas se eliminaron.');
            loadExams();
        }).fail(showError);
    });

    $('#draw-form').on('submit', function (event) {
        event.preventDefault();
        const count = Number($('#draw-count').val());
        if (!selectedExamId || !Number.isInteger(count) || count < 1) {
            showToast('Ingresá una cantidad válida de preguntas.', true);
            return;
        }
        request('draw_questions', { exam_id: selectedExamId, count: count }).done(function (response) {
            const result = $('#draw-result').empty();
            result.append($('<h4>').text('¡Estas son tus ' + response.questions.length + ' preguntas!'));
            response.questions.forEach(function (question, index) {
                const item = $('<div>', { class: 'drawn-question' })
                    .append($('<span>', { class: 'drawn-number', text: String(index + 1).padStart(2, '0') }))
                    .append($('<p>').text(question.question_text));
                if (question.answer_text) {
                    item.append($('<details>').append($('<summary>').text('Ver respuesta')).append($('<p>').text(question.answer_text)));
                }
                result.append(item);
            });
            result.prop('hidden', false);
            result[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }).fail(showError);
    });

    request('session').done(function (response) {
        if (response.user) {
            setAuthenticated(response.user.name);
        } else {
            setUnauthenticated();
        }
    }).fail(showError);
});
