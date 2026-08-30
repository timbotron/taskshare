;(function () {
  'use strict'

  // --- Home demo (CODE-77 scaffold): proves the no-build frontend is wired ---
  var demoRoot = document.getElementById('ts-demo')
  if (demoRoot) {
    var count = 0
    var Demo = {
      view: function () {
        return m('div', { class: 'space-y-3' }, [
          m('p', { class: 'text-sm text-gray-500' }, 'Mithril is wired. Clicks: ' + count),
          m('button', { class: 'btn', onclick: function () { count++ } }, 'Click me'),
        ])
      },
    }
    m.mount(demoRoot, Demo)
  }

  // --- Board view (CODE-81 hydrate + CODE-82 CRUD + CODE-83 complete toggle) ---
  var boardRoot = document.getElementById('board-app')
  if (!boardRoot || !window.__TASKSHARE__) return

  var state = window.__TASKSHARE__
  var slug = state.board.slug
  var isOwner = state.is_owner
  var base = '/b/' + slug

  // Capability seams. CODE-86 widens these to (isOwner || permissions.allow_*).
  // Adding/editing tasks and renaming lists are owner-only (not shareable perms).
  var canManage = isOwner     // new/rename/delete lists, add/edit tasks, list options
  var canComplete = isOwner   // toggle a task's completed flag

  state.lists.forEach(initListUi)
  function initListUi (list) {
    list._ui = { menu: false, editingName: false, nameValue: list.title, adding: false, addValue: '', editing: false }
    list.tasks.forEach(function (t) { t._editValue = t.text })
  }

  // XHR helper. Mithril redraws automatically when the promise settles.
  function api (method, url, body) {
    return m.request({ method: method, url: url, body: body }).catch(function (err) {
      window.alert((err && err.response && err.response.error) || 'Something went wrong.')
      throw err
    })
  }

  // --- list actions ---
  function addList () {
    api('POST', base + '/lists', {}).then(function (list) {
      initListUi(list)
      list._ui.editingName = true // drop straight into naming the new list
      list._ui.menu = true
      state.lists.push(list)
    })
  }
  function saveListName (list) {
    var title = (list._ui.nameValue || '').trim()
    if (!title) return
    api('PUT', base + '/lists/' + list.id, { title: title }).then(function () {
      list.title = title
      list._ui.editingName = false
    })
  }
  function deleteList (list) {
    if (!window.confirm('Delete this list and all its tasks?')) return
    api('DELETE', base + '/lists/' + list.id).then(function () {
      var i = state.lists.indexOf(list)
      if (i >= 0) state.lists.splice(i, 1)
    })
  }
  function toggleEditTasks (list) {
    list._ui.editing = !list._ui.editing
    if (list._ui.editing) list.tasks.forEach(function (t) { t._editValue = t.text })
  }

  // --- task actions ---
  function addTask (list) {
    var text = (list._ui.addValue || '').trim()
    if (!text) return
    api('POST', base + '/lists/' + list.id + '/tasks', { text: text }).then(function (task) {
      task._editValue = task.text
      list.tasks.push(task)
      list._ui.addValue = '' // keep the input open for the next one
    })
  }
  function saveTaskText (task) {
    var text = (task._editValue || '').trim()
    if (!text) return
    api('PUT', base + '/tasks/' + task.id, { text: text }).then(function () { task.text = text })
  }
  // Set (not blindly toggle) the completed flag; click a struck task to un-strike.
  function toggleComplete (task) {
    var next = task.completed ? 0 : 1
    api('PUT', base + '/tasks/' + task.id + '/complete', { completed: next }).then(function () {
      task.completed = next
    })
  }

  function focusOnCreate (vnode) { vnode.dom.focus() }

  // --- components ---
  var TaskRow = {
    view: function (vnode) {
      var list = vnode.attrs.list
      var task = vnode.attrs.task
      var checked = !!task.completed

      // Left check button: toggles complete. Interactive only for those allowed;
      // otherwise a static indicator so viewers still see completion state.
      var checkAttrs = { class: 'check-btn' + (checked ? ' is-checked' : '') }
      var check = canComplete
        ? m('button', Object.assign({ title: 'Complete / un-complete', onclick: function () { toggleComplete(task) } }, checkAttrs), '✓')
        : m('span', checkAttrs, '✓')

      // Edit mode (entered from the list options): the text becomes an input.
      var body = (canManage && list._ui.editing)
        ? m('input', {
            class: 'w-full rounded border border-gray-300 px-2 py-1 text-sm',
            value: task._editValue,
            oninput: function (e) { task._editValue = e.target.value },
            onkeyup: function (e) { if (e.key === 'Enter') saveTaskText(task) },
            onblur: function () { saveTaskText(task) },
          })
        : m('span', { class: 'task-text flex-1' + (checked ? ' is-done' : '') }, task.text)

      return m('li', { class: 'task-row' }, [check, body])
    },
  }

  var ListMenu = {
    view: function (vnode) {
      var list = vnode.attrs.list
      return m('div', { class: 'mb-2 flex flex-wrap gap-1' }, [
        m('button', { class: 'menu-btn', onclick: function () { list._ui.adding = true } }, 'Add task'),
        m('button', { class: 'menu-btn', onclick: function () { list._ui.editingName = true; list._ui.nameValue = list.title } }, 'Edit name'),
        m('button', { class: 'menu-btn', onclick: function () { toggleEditTasks(list) } }, list._ui.editing ? 'Done editing' : 'Edit tasks'),
        m('button', { class: 'menu-btn text-red-700', onclick: function () { deleteList(list) } }, 'Delete list'),
      ])
    },
  }

  var ListHeader = {
    view: function (vnode) {
      var list = vnode.attrs.list
      if (canManage && list._ui.editingName) {
        return m('div', { class: 'mb-2 flex gap-1' }, [
          m('input', {
            class: 'w-full rounded border border-gray-300 px-2 py-1 font-semibold',
            value: list._ui.nameValue,
            oncreate: focusOnCreate,
            oninput: function (e) { list._ui.nameValue = e.target.value },
            onkeyup: function (e) { if (e.key === 'Enter') saveListName(list) },
          }),
          m('button', { class: 'menu-btn', onclick: function () { saveListName(list) } }, 'Save'),
        ])
      }
      return m('div', { class: 'mb-2 flex items-center justify-between gap-2' }, [
        m('h3', { class: 'font-semibold' }, list.title),
        canManage
          ? m('button', {
              class: 'options-btn',
              title: 'List options',
              onclick: function () { list._ui.menu = !list._ui.menu },
            }, list._ui.menu ? '▴' : '▾')
          : null,
      ])
    },
  }

  var AddTask = {
    view: function (vnode) {
      var list = vnode.attrs.list
      return m('div', { class: 'mt-2 flex gap-1' }, [
        m('input', {
          class: 'w-full rounded border border-gray-300 px-2 py-1 text-sm',
          placeholder: 'New task',
          value: list._ui.addValue,
          oncreate: focusOnCreate,
          oninput: function (e) { list._ui.addValue = e.target.value },
          onkeyup: function (e) { if (e.key === 'Enter') addTask(list) },
        }),
        m('button', { class: 'menu-btn', onclick: function () { addTask(list) } }, 'Add'),
        m('button', { class: 'menu-btn', onclick: function () { list._ui.adding = false; list._ui.addValue = '' } }, 'Done'),
      ])
    },
  }

  var ListCard = {
    view: function (vnode) {
      var list = vnode.attrs.list
      return m('div', { class: 'list-card' }, [
        m(ListHeader, { list: list }),
        canManage && list._ui.menu ? m(ListMenu, { list: list }) : null,
        list.tasks.length
          ? m('ul', { class: 'space-y-1' }, list.tasks.map(function (t) {
              return m(TaskRow, { key: t.id, list: list, task: t })
            }))
          : m('p', { class: 'text-sm text-gray-400' }, 'No tasks yet.'),
        canManage && list._ui.adding ? m(AddTask, { list: list }) : null,
      ])
    },
  }

  var BoardApp = {
    view: function () {
      return m('div', [
        canManage
          ? m('div', { class: 'mb-4' }, m('button', { class: 'btn', onclick: addList }, '+ New list'))
          : null,
        state.lists.length
          ? m('div', { class: 'board-grid' }, state.lists.map(function (list) {
              return m(ListCard, { key: list.id, list: list })
            }))
          : m('p', { class: 'text-gray-500' }, canManage ? 'No lists yet. Add one to get started.' : 'This board has no lists yet.'),
      ])
    },
  }

  m.mount(boardRoot, BoardApp)
})()
