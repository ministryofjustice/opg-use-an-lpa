(() => {
  var __create = Object.create;
  var __defProp = Object.defineProperty;
  var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __getProtoOf = Object.getPrototypeOf;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __commonJS = (cb, mod) => function __require() {
    return mod || (0, cb[__getOwnPropNames(cb)[0]])((mod = { exports: {} }).exports, mod), mod.exports;
  };
  var __copyProps = (to, from, except, desc) => {
    if (from && typeof from === "object" || typeof from === "function") {
      for (let key of __getOwnPropNames(from))
        if (!__hasOwnProp.call(to, key) && key !== except)
          __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
    }
    return to;
  };
  var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(
    // If the importer is in node compatibility mode or this is not an ESM
    // file that has been converted to a CommonJS file using a Babel-
    // compatible transform (i.e. "__esModule" has not been set), then set
    // "default" to the CommonJS "module.exports" for node compatibility.
    isNodeMode || !mod || !mod.__esModule ? __defProp(target, "default", { value: mod, enumerable: true }) : target,
    mod
  ));

  // globals:./types/index
  var require_types = __commonJS({
    "globals:./types/index"(exports, module) {
      module.exports = __imposter_types;
    }
  });

  // src/index.mjs
  var import_types = __toESM(require_types(), 1);

  // src/lpas/4UX3.json
  var UX3_default = {
    uid: "M-789Q-P4DF-4UX3",
    status: "registered",
    registrationDate: "2024-01-12",
    updatedAt: "2024-01-12T23:00:00Z",
    lpaType: "personal-welfare",
    channel: "online",
    donor: {
      uid: "eda719db-8880-4dda-8c5d-bb9ea12c236f",
      firstNames: "Feeg",
      lastName: "Bundlaaaa",
      address: {
        line1: "74 Cloob Close",
        town: "Mahhhhhhhhhh",
        postcode: "TP6 8EX",
        country: "GB"
      },
      dateOfBirth: "1970-01-24",
      email: "nobody@not.a.real.domain",
      contactLanguagePreference: "en",
      identityCheck: {
        checkedAt: "2024-01-10T23:00:00Z",
        type: "one-login"
      }
    },
    attorneys: [
      {
        uid: "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
        firstNames: "Herman",
        lastName: "Seakrest",
        address: {
          line1: "81 NighOnTimeWeBuiltIt Street",
          town: "Mahhhhhhhhhh",
          postcode: "PC4 6UZ",
          country: "GB"
        },
        dateOfBirth: "1982-07-24",
        status: "active",
        channel: "paper",
        signedAt: "2024-01-10T23:00:00Z"
      },
      {
        uid: "9201a0b8-70a2-47db-93f2-c7510b4210ae",
        firstNames: "Jessica",
        lastName: "Seakrest",
        address: {
          line1: "81 NighOnTimeWeBuiltIt Street",
          town: "Mahhhhhhhhhh",
          country: "GB"
        },
        dateOfBirth: "1984-04-13",
        status: "replacement",
        channel: "online",
        signedAt: "2024-01-10T23:00:00Z"
      }
    ],
    trustCorporations: [
      {
        uid: "1d95993a-ffbb-484c-b2fe-f4cca51801da",
        name: "Trust us Corp.",
        companyNumber: "666123321",
        address: {
          line1: "103 Line 1",
          town: "Town",
          country: "GB"
        },
        status: "active",
        channel: "paper",
        signedAt: "2024-01-10T23:00:00Z"
      }
    ],
    certificateProvider: {
      uid: "6808960d-12cf-47c5-a2bc-3177deb8599c",
      firstNames: "Vone",
      lastName: "Spust",
      address: {
        line1: "122111 Zonnington Way",
        town: "Mahhhhhhhhhh",
        country: "GB"
      },
      channel: "online",
      email: "a@example.com",
      phone: "070009000",
      signedAt: "2024-01-10T23:00:00Z",
      identityCheck: {
        checkedAt: "2024-01-10T23:00:00Z",
        type: "one-login"
      }
    },
    lifeSustainingTreatmentOption: "option-a",
    signedAt: "2024-01-10T23:00:00Z",
    howAttorneysMakeDecisions: "jointly",
    whenTheLpaCanBeUsed: "when-capacity-lost"
  };

  // src/lpas/lpas.mjs
  var lpaData = [
    UX3_default
  ];
  var getLpa = (uid) => {
    const lpas = getList([uid]);
    return lpas.lpas.pop();
  };
  var getList = (uids) => {
    const lpas = [];
    for (const lpa of lpaData) {
      if (uids.includes(lpa.uid)) {
        lpas.push(lpa);
      }
    }
    return {
      lpas
    };
  };

  // src/index.mjs
  var opId = import_types.context.operation.getOperationId();
  import_types.logger.info("Operation is " + opId);
  var code = 400;
  var response = "";
  if (opId === "getLpa") {
    let data = getLpa(import_types.context.request.pathParams.uid);
    if (data.uid === void 0) {
      code = 404;
    } else {
      code = 200;
      response = JSON.stringify(data);
    }
  } else if (opId === "getList") {
    if (import_types.context.request.body !== null) {
      let uids = JSON.parse(import_types.context.request.body).uids;
      code = 200;
      response = JSON.stringify(getList(uids));
      import_types.logger.info(uids.length + " lpas requested");
    }
  } else if (opId === "healthCheck") {
    code = 200;
    response = JSON.stringify({ "status": "OK" });
    import_types.logger.info("healthcheck requested");
  }
  if (response === "") {
    (0, import_types.respond)().withStatusCode(code).usingDefaultBehaviour();
  } else {
    (0, import_types.respond)().withStatusCode(code).withData(response);
  }
})();
