import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import relativeTime from 'dayjs/plugin/relativeTime'
import { computed, inject } from 'vue'
import { usePage } from '@inertiajs/vue3'

dayjs.extend(utc)
dayjs.extend(timezone)
dayjs.extend(relativeTime)

export default () => {
  const $http = inject('$http')
  const timezone = computed(() => usePage().props.user?.timezone || 'UTC')
  const timeFormat = 'h:mma'
  const formats = {
    full: `MMMM D, YYYY ${timeFormat}`,
    abbr: `MMM D, YYYY ${timeFormat}`,
    short: `MMM D ${timeFormat}`,
    time: timeFormat,
    abbr_date: 'MMM D, YYYY',
  }
  const fetchTimezones = async () => {
    try {
      const { data } = await $http.get('/timezones')
      return data
    } catch (e) {
      return {}
    }
  }
  const getDate = (date, offset = false) => (date ? dayjs(date) : dayjs()).tz(timezone.value, offset)
  const displayDate = (date, format, offset = false) => getDate(date, offset).format(formats[format] || format)
  const fromNow = (date) => getDate(date).fromNow()

  return {
    dayjs,
    timezone,
    displayDate,
    getDate,
    fromNow,
    fetchTimezones,
  }
}
