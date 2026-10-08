import zeroZeroFourSeven from './0047.json';
import zeroOneThreeEight from './0138.json';
import zeroTwoFiveTwo from './0252.json';
import zeroThreeFourFour from './0344.json';
import zeroFourThreeFive from './0435.json';
import zeroFiveTwoSix from './0526.json';
import zeroSixOneSeven from './0617.json';
import oneFourFiveOne from './1451.json';
import sixThreeSixOne from './6361.json';
import sevenTwoThreeSeven from './7237.json';

const lpaData = [
  zeroZeroFourSeven,
  zeroOneThreeEight,
  zeroTwoFiveTwo,
  zeroThreeFourFour,
  zeroFourThreeFive,
  zeroFiveTwoSix,
  zeroSixOneSeven,
  oneFourFiveOne,
  sixThreeSixOne,
  sevenTwoThreeSeven,
]

// adds hyphens to the uid
const formatUid = uid =>
  uid.match(/.{1,4}/g).join('-')

const getLpa = uid => {
  for (const lpa of lpaData) {
    if (lpa.uId === formatUid(uid)) {
      return lpa
    }
  }
}

export {getLpa}
