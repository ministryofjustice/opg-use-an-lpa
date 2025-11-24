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

  // external-global-plugin:@imposter-js/types
  var require_types = __commonJS({
    "external-global-plugin:@imposter-js/types"(exports, module) {
      module.exports = __imposter_types;
    }
  });

  // src/index.ts
  var import_types2 = __toESM(require_types());

  // src/codes/codes.ts
  var import_types = __toESM(require_types());

  // src/enum.ts
  var ExpiryReason = /* @__PURE__ */ ((ExpiryReason2) => {
    ExpiryReason2[ExpiryReason2["cancelled"] = 0] = "cancelled";
    ExpiryReason2[ExpiryReason2["paper_to_digital"] = 30] = "paper_to_digital";
    ExpiryReason2[ExpiryReason2["first_time_use"] = 730] = "first_time_use";
    return ExpiryReason2;
  })(ExpiryReason || {});

  // src/codes/seeding-data.json
  var seeding_data_default = [
    {
      code: "NYGUAMNB46JQ",
      active: true,
      actor: "700000001805",
      last_updated_date: "2020-06-22",
      lpa: "700000000526",
      dob: "1975-10-05",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "8EFXFEF48WJ4",
      active: true,
      actor: "700000000971",
      last_updated_date: "2020-06-22",
      lpa: "700000000138",
      dob: "1948-11-01",
      expiry_date: 1631180477,
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data: Expired Code"
    },
    {
      code: "3JHKF3C6D9W8",
      active: true,
      actor: "700000001755",
      last_updated_date: "2020-06-22",
      lpa: "700000000526",
      dob: "1948-11-01",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "CXAY6GPCQ4X3",
      active: true,
      actor: "700000001573",
      last_updated_date: "2020-06-22",
      lpa: "700000000435",
      dob: "1948-11-01",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "EEWNGCGW6LWU",
      active: true,
      actor: "700000001987",
      last_updated_date: "2020-06-22",
      lpa: "700000000617",
      dob: "1975-10-05",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "QRTXRCFRLK46",
      active: true,
      actor: "700000116322",
      last_updated_date: "2020-06-22",
      lpa: "700000000138",
      dob: "1990-03-17",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "E9YRUTPM6RBW",
      active: true,
      actor: "700000000799",
      last_updated_date: "2020-06-22",
      lpa: "700000000047",
      dob: "1948-11-01",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "6HFCLATAGLEY",
      active: true,
      actor: "700000000849",
      last_updated_date: "2020-06-22",
      lpa: "700000000047",
      dob: "1975-10-05",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "W7D4MT7HAQEH",
      active: true,
      actor: "700000001938",
      last_updated_date: "2020-06-22",
      lpa: "700000000617",
      dob: "1948-11-01",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "4UAL33PEQNAY",
      active: true,
      actor: "700000000997",
      last_updated_date: "2020-06-22",
      lpa: "700000000138",
      dob: "1975-10-05",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "5VBM44QFRMBZ",
      active: true,
      actor: "700000151998",
      last_updated_date: "2020-06-22",
      lpa: "700000000138",
      dob: "1948-11-01",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data: Trust Corporation actor"
    },
    {
      code: "3YKAJNDD8P3N",
      active: true,
      actor: "700000000815",
      last_updated_date: "2020-06-22",
      lpa: "700000000047",
      dob: "1990-05-04",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:",
      has_paper_verification_code: true
    },
    {
      code: "6CFKNNFLPCP4",
      active: true,
      actor: "700000001391",
      last_updated_date: "2020-06-22",
      lpa: "700000000344",
      dob: "1948-11-01",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "RQ3W8G4EYRQJ",
      active: true,
      actor: "700000001235",
      last_updated_date: "2020-06-22",
      lpa: "700000000252",
      dob: "1975-10-05",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "44THDVFJ4P4Y",
      active: true,
      actor: "700000001599",
      last_updated_date: "2020-06-22",
      lpa: "700000000435",
      dob: "1975-10-05",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "XW34H3HYFDDL",
      active: true,
      actor: "700000001219",
      last_updated_date: "2020-06-22",
      lpa: "700000000252",
      dob: "1948-11-01",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "PEYBDGT6AJ7U",
      active: true,
      actor: "700000136098",
      last_updated_date: "2020-06-22",
      lpa: "700000000344",
      dob: "1975-10-05",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    },
    {
      code: "P-1234-1234-1234-12",
      actor: "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
      last_updated_date: "2025-01-10",
      lpa: "M-7890-0400-4000",
      generated_date: "2025-01-10",
      Comment: "Seeded Data: Active, unused code"
    },
    {
      code: "P-5678-5678-5678-56",
      actor: "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
      last_updated_date: "2025-01-10",
      lpa: "M-7890-0400-4000",
      generated_date: "2024-01-10",
      expiry_date: 1736499995,
      expiry_reason: "first_time_use",
      Comment: "Seeded Data: Expired as used initially over 2 years ago."
    },
    {
      code: "P-3456-3456-3456-34",
      actor: "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
      last_updated_date: "2025-01-10",
      lpa: "M-7890-0400-4000",
      generated_date: "2025-01-10",
      expiry_date: 1736499995,
      expiry_reason: "cancelled",
      Comment: "Seeded Data: Expired as cancelled by external process"
    },
    {
      code: "E9YRUTPM6RLK",
      active: true,
      actor: "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
      last_updated_date: "2020-06-22",
      lpa: "M-7890-0400-4000",
      dob: "1982-07-24",
      expiry_date: "valid",
      generated_date: "2020-06-22",
      status_details: "Imported",
      Comment: "Seeded Data:"
    }
  ];

  // src/codes/seeding-data.ts
  var codeData = seeding_data_default;
  var seeding_data_default2 = codeData;

  // src/codes/codes.ts
  function loadCodes() {
    let codes = [];
    const store = import_types.stores.open("codeData");
    for (const code2 of seeding_data_default2) {
      if (store.hasItemWithKey(code2.code)) {
        let alteredCode = JSON.parse(store.load(code2.code));
        codes.push(alteredCode);
        import_types.logger.debug("Using altered code " + alteredCode.code);
      } else {
        codes.push(code2);
      }
    }
    import_types.logger.info("Loaded " + codes.length + " codes");
    return codes;
  }
  function codeExists(lpaUid, actorUid) {
    let code2 = loadCodes().find(({ lpa, actor }) => lpa === lpaUid && actor === actorUid);
    if (code2 === void 0) {
      import_types.logger.debug("Code not found for " + lpaUid + " & " + actorUid);
      return null;
    }
    import_types.logger.debug("Code found " + code2.code);
    return code2;
  }
  function getCode(activationCode) {
    let code2 = loadCodes().find(({ code: code3 }) => code3 === activationCode);
    if (code2 === void 0) {
      import_types.logger.debug("Code not found " + activationCode);
      return null;
    }
    import_types.logger.debug("Code found " + code2.code);
    return code2;
  }
  function revokeCode(activationCode) {
    let code2 = getCode(activationCode);
    if (code2 === null || !code2.active) {
      return null;
    }
    code2.active = false;
    code2.status_details = "Revoked";
    const store = import_types.stores.open("codeData");
    store.save(code2.code, JSON.stringify(code2));
    return code2;
  }
  function expireCode(paperVerificationCode, reason) {
    import_types.logger.debug("Expired code " + reason);
    let code2 = getCode(paperVerificationCode);
    if (code2 === null) {
      return null;
    }
    const now = /* @__PURE__ */ new Date();
    now.setHours(0, 0, 0, 0);
    const expiry = now.setDate(now.getDate() + reason);
    code2.expiry_date = Math.floor(expiry / 1e3);
    code2.expiry_reason = ExpiryReason[reason];
    const store = import_types.stores.open("codeData");
    store.save(code2.code, JSON.stringify(code2));
    return code2;
  }
  function isNotExpired(code2) {
    if (code2.expiry_date === "valid") {
      import_types.logger.debug("Code " + code2.code + " will never expire");
      return true;
    }
    const ttl = Math.floor((/* @__PURE__ */ new Date()).getTime() / 1e3);
    import_types.logger.debug("code date: " + code2.expiry_date + " ttl: " + ttl);
    if (typeof code2.expiry_date === "number") {
      return code2.expiry_date > ttl;
    }
    throw Error(
      "expiry_date field for code " + code2.lpa + " in seeding-data.json is incorrectly formatted"
    );
  }

  // src/index.ts
  var opId = import_types2.context.operation.operationId;
  import_types2.logger.info("Operation is " + opId);
  var code = 400;
  var response = "";
  function unix2date(date) {
    const dateObj = new Date(date * 1e3);
    return dateObj.toISOString().substring(0, 10);
  }
  if (opId === "api.resources.handle_healthcheck") {
    code = 200;
    response = JSON.stringify("OK");
  } else if (opId === "api.resources.validate_route") {
    if (import_types2.context.request.body !== null) {
      let params = JSON.parse(import_types2.context.request.body);
      let activationCode = getCode(params.code);
      import_types2.logger.debug("Loaded code " + JSON.stringify(activationCode));
      if (activationCode !== null && activationCode.dob === params.dob && activationCode.lpa === params.lpa && activationCode.active === true && isNotExpired(activationCode)) {
        import_types2.logger.info("Code " + activationCode.code + " matched parameters");
        if (activationCode.has_paper_verification_code) {
          response = JSON.stringify({ "actor": activationCode.actor, "has_paper_verification_code": true });
        } else {
          response = JSON.stringify({ "actor": activationCode.actor });
        }
      } else {
        response = JSON.stringify({ "actor": null });
      }
      code = 200;
    }
  } else if (opId === "api.resources.revoke_route") {
    if (import_types2.context.request.body !== null) {
      let params = JSON.parse(import_types2.context.request.body);
      let activationCode = revokeCode(params.code);
      import_types2.logger.debug("Loaded code " + JSON.stringify(activationCode));
      if (activationCode !== null) {
        import_types2.logger.info("Code " + activationCode.code + " revoked");
        response = JSON.stringify({ "codes revoked": 1 });
      } else {
        response = JSON.stringify({ "codes revoked": 0 });
      }
      code = 200;
    }
  } else if (opId === "api.resources.actor_code_exists_route") {
    if (import_types2.context.request.body !== null) {
      let params = JSON.parse(import_types2.context.request.body);
      let activationCode = codeExists(params.lpa, params.actor);
      import_types2.logger.debug("Loaded code " + JSON.stringify(activationCode));
      if (activationCode !== null && activationCode.active === true && isNotExpired(activationCode)) {
        import_types2.logger.info("Code " + activationCode.code + " matched parameters");
        response = JSON.stringify({ "Created": activationCode.generated_date });
      } else {
        response = JSON.stringify({ "Created": null });
      }
      code = 200;
    }
  } else if (opId === "api.resources.pvc_validate_route") {
    if (import_types2.context.request.body !== null) {
      let params = JSON.parse(import_types2.context.request.body);
      let activationCode = getCode(params.code);
      import_types2.logger.debug("Loaded code " + JSON.stringify(activationCode));
      if (activationCode !== null) {
        import_types2.logger.info("Code " + activationCode.code + " matched parameters");
        const responseData = {
          lpa: activationCode.lpa,
          actor: activationCode.actor
        };
        if (activationCode.expiry_date !== void 0) {
          responseData.expiry_date = unix2date(activationCode.expiry_date);
          responseData.expiry_reason = activationCode.expiry_reason;
        }
        response = JSON.stringify(responseData);
        code = 200;
      } else {
        code = 404;
      }
    }
  } else if (opId === "api.resources.pvc_expire_route") {
    if (import_types2.context.request.body !== null) {
      let params = JSON.parse(import_types2.context.request.body);
      let activationCode = expireCode(
        params.code,
        ExpiryReason[params.expiry_reason]
      );
      import_types2.logger.debug("Loaded code " + JSON.stringify(activationCode));
      if (activationCode !== null) {
        import_types2.logger.info(
          "Code " + activationCode.code + " expires in " + ExpiryReason[activationCode.expiry_reason] + " days"
        );
        const responseData = {
          expiry_date: unix2date(activationCode.expiry_date)
        };
        response = JSON.stringify(responseData);
        code = 200;
      } else {
        code = 404;
      }
    }
  }
  if (response === "") {
    (0, import_types2.respond)().withStatusCode(code).usingDefaultBehaviour();
  } else {
    (0, import_types2.respond)().withStatusCode(code).withHeader("Content-Type", "application/json").withData(response);
  }
})();
