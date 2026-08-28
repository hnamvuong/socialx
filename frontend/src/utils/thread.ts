import type {
  Post,
  ThreadNode,
} from '@/types/post'

export function buildThreadTree(
  root: Post,
  replies: Post[],
): ThreadNode {
  const nodeMap =
    new Map<number, ThreadNode>()

  const rootNode: ThreadNode = {
    post: root,
    children: [],
  }

  nodeMap.set(
    root.id,
    rootNode,
  )

  for (const reply of replies) {
    nodeMap.set(
      reply.id,
      {
        post: reply,
        children: [],
      },
    )
  }

  for (const reply of replies) {
    const node =
      nodeMap.get(
        reply.id,
      )

    if (!node) {
      continue
    }

    const parentId = reply.parent_post_id

    if (!parentId) {
      rootNode.children.push(
        node,
      )

      continue
    }

    const parentNode =
      nodeMap.get(
        parentId,
      )

    if (parentNode) {
      parentNode.children.push(
        node,
      )
    } else {
      rootNode.children.push(
        node,
      )
    }
  }

  return rootNode
}
