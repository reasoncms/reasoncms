function getCreditCardType(accountNumber)
{
  //start without knowing the credit card type
  var result = "unknown";

  //first check for MasterCard
  if (/^5[1-5]/.test(accountNumber))
  {
    result = "Mastercard";
  }

  //then check for Visa
  else if (/^4/.test(accountNumber))
  {
    result = "Visa";
  }

  //then check for AmEx
  else if (/^3[47]/.test(accountNumber))
  {
    result = "American Express";
  }

  //then check for Discover
  //have to get rid of possible - for Discover or just take it into account in regex
  else if(/^6011/.test(accountNumber) || /^64[4-9]/.test(accountNumber) || /^65/.test(accountNumber) || /^3[47]/.test(accountNumber))
  {
    result = "Discover";
  }

  return result;
}

function showCreditCardType(accountNumber)
{
  var type = getCreditCardType(accountNumber);
  switch (type)
  {
    case "Mastercard":
      //show MasterCard icon
      break;

    case "Visa":
      //show Visa icon
      break;

    case "American Express":
      //show American Express icon
      break;

    case "Discover":
      //show Discover icon
      break;

    default:
      //Error
  }
}
