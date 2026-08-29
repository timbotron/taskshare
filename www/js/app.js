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

  // --- Board view (CODE-81): hydrate from the PHP-embedded state, no data XHR ---
  // Read render only; list/task CRUD is CODE-82, complete-toggle is CODE-83.
  var boardRoot = document.getElementById('board-app')
  if (boardRoot && window.__TASKSHARE__) {
    var state = window.__TASKSHARE__

    var TaskRow = {
      view: function (vnode) {
        var t = vnode.attrs.task
        return m('li', { class: 'task-row' + (t.completed ? ' is-done' : '') }, t.text)
      },
    }

    var ListCard = {
      view: function (vnode) {
        var list = vnode.attrs.list
        return m('div', { class: 'list-card' }, [
          m('h3', { class: 'mb-2 font-semibold' }, list.title),
          list.tasks.length
            ? m('ul', { class: 'space-y-1' }, list.tasks.map(function (t) {
                return m(TaskRow, { key: t.id, task: t })
              }))
            : m('p', { class: 'text-sm text-gray-400' }, 'No tasks yet.'),
        ])
      },
    }

    var Board = {
      view: function () {
        if (!state.lists.length) {
          return m('p', { class: 'text-gray-500' }, 'This board has no lists yet.')
        }
        return m('div', { class: 'board-grid' }, state.lists.map(function (list) {
          return m(ListCard, { key: list.id, list: list })
        }))
      },
    }

    m.mount(boardRoot, Board)
  }
})()
