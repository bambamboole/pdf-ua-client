const managedKeysByType: Record<string, string[]> = {
  "key-value": ["fields"],
  table: ["columns", "numberRows", "style"],
};

export function managedConfigKeys(type: string): Set<string> {
  return new Set(managedKeysByType[type] ?? []);
}
