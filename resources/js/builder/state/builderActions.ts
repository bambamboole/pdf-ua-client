import { createContext, useContext } from "react";
import type { Json, PageNumberPosition } from "../types";
import type { UpdateBlockData, UpdateDataField } from "../blocks/types";

export interface BuilderActions {
  onSelectBlock: (uid: string) => void;
  onRemoveBlock: (uid: string) => void;
  onRemoveRow: (uid: string) => void;
  onSetRowWidths: (rowUid: string, widths: string[]) => void;
  onUpdateBlockId: (uid: string, id: string) => void;
  onUpdateBlockConfig: (uid: string, config: Json) => void;
  onUpdateDataField: UpdateDataField;
  onUpdateBlockData: UpdateBlockData;
  onUpdateFooterRepeat: (repeat: boolean) => void;
  onUpdatePageNumbers: (position: PageNumberPosition) => void;
}

const noop = (): void => undefined;

const BuilderActionsContext = createContext<BuilderActions>({
  onSelectBlock: noop,
  onRemoveBlock: noop,
  onRemoveRow: noop,
  onSetRowWidths: noop,
  onUpdateBlockId: noop,
  onUpdateBlockConfig: noop,
  onUpdateDataField: noop,
  onUpdateBlockData: noop,
  onUpdateFooterRepeat: noop,
  onUpdatePageNumbers: noop,
});

export const BuilderActionsProvider = BuilderActionsContext.Provider;

export function useBuilderActions(): BuilderActions {
  return useContext(BuilderActionsContext);
}
