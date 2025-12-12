import fourThousand from './4000.json';

const lpaData = [
  fourThousand
]

const getLpa = uid => {
  const lpas = getList([uid])

  return lpas.lpas.pop()
}

const getList = uids => {
  const lpas = [];

  for (const lpa of lpaData) {
    if (uids.includes(lpa.uid)) {
      lpas.push(lpa)
    }
  }

  return {
    lpas: lpas
  }
}

export {getLpa, getList}
