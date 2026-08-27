export function formatPostTime(
  value: string,
): string {
  const date =
    new Date(value)

  const now =
    new Date()

  const diffMilliseconds =
    now.getTime() -
    date.getTime()

  const diffSeconds =
    Math.max(
      0,
      Math.floor(
        diffMilliseconds / 1000,
      ),
    )

  if (diffSeconds < 60) {
    return 'vừa xong'
  }

  const diffMinutes =
    Math.floor(
      diffSeconds / 60,
    )

  if (diffMinutes < 60) {
    return `${diffMinutes} phút`
  }

  const diffHours =
    Math.floor(
      diffMinutes / 60,
    )

  if (diffHours < 24) {
    return `${diffHours} giờ`
  }

  const diffDays =
    Math.floor(
      diffHours / 24,
    )

  if (diffDays < 7) {
    return `${diffDays} ngày`
  }

  return new Intl.DateTimeFormat(
    'vi-VN',
    {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    },
  ).format(date)
}

export function formatFullDateTime(
  value: string,
): string {
  return new Intl.DateTimeFormat(
    'vi-VN',
    {
      dateStyle: 'medium',
      timeStyle: 'short',
    },
  ).format(
    new Date(value),
  )
}
