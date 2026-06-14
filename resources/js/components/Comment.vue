<template>
  <div class="commenter__comment py-4 border-t border-40" :dusk="`commenter-comment-${comment.id.value}`">
    <div class="font-light text-80 text-sm">
      <template v-if="hasCommenter">
        <a class="link-default" :href="commenterUrl" v-text="commenter"></a>
        said
      </template>
      <template v-else>
        Written
      </template>
      {{ date }}
    </div>

    <div class="mt-2 commenter__comment-body" v-html="commentString"></div>
  </div>
</template>

<script>
export default {
  props: {
    comment: {
      type: Object,
      required: true,
    },
  },

  computed: {
    commentString() {
      return _.find(this.comment.fields, { attribute: 'comment' }).value
    },

    commenter() {
      const field = _.find(this.comment.fields, { attribute: 'commenter' })
      return field ? field.value : null
    },

    commenterUrl() {
      const field = _.find(this.comment.fields, { attribute: 'commenter' })
      const id = field ? field.belongsToId : null
      const uriKey = field && field.resourceName ? field.resourceName : 'users'
      return `${Nova.config('base')}/resources/${uriKey}/${id}`
    },

    date() {
      const now = moment()
      const created = _.find(this.comment.fields, { attribute: 'created_at' }).value
      const date = moment.utc(created).tz(moment.tz.guess())

      if (date.isSame(now, 'minute')) return 'just now'
      if (date.isSame(now, 'day')) return `at ${date.format('LT')}`
      if (date.isSame(now, 'year')) return `on ${date.format('MMM D')}`
      return `on ${date.format('ll')}`
    },

    hasCommenter() {
      return Boolean(this.commenter)
    },
  },
}
</script>
