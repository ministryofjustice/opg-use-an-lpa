import fourUXthree from './4UX3.json';

const lpaData = [
  fourUXthree
]

const getLpa = uid => {
  const lpas = self.getList([uid])

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
